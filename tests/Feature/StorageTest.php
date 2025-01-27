<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use Memcached;
use org\bovigo\vfs\vfsStream;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Redis;
use Securepoint\TokenBucket\Rate;
use Securepoint\TokenBucket\Storage\FileStorage;
use Securepoint\TokenBucket\Storage\MemcachedStorage;
use Securepoint\TokenBucket\Storage\PDOStorage;
use Securepoint\TokenBucket\Storage\PHPRedisStorage;
use Securepoint\TokenBucket\Storage\PredisStorage;
use Securepoint\TokenBucket\Storage\SessionStorage;
use Securepoint\TokenBucket\Storage\SingleProcessStorage;
use Securepoint\TokenBucket\Storage\Storage;
use Securepoint\TokenBucket\Storage\StorageException;
use Securepoint\TokenBucket\TokenBucket;

/**
 * Tests for Storage implementations.
 *
 * If you want to run vendor specific tests you should provide these
 * environment variables:
 *
 * - MYSQL_DSN, MYSQL_USER
 * - PGSQL_DSN, PGSQL_USER
 * - MEMCACHE_HOST
 * - REDIS_URI
 *
 * @license WTFPL
 * @see Storage
 */
class StorageTest extends TestCase
{
    /**
     * @var Storage The tested storage;
     */
    private Storage $storage;

    protected function tearDown(): void
    {
        if ($this->storage->isBootstrapped()) {
            $this->storage->remove();
        }
    }

    /**
     * Provides uninitialized storage implementations.
     *
     * @return callable[][] Storage factories.
     */
    public static function provideStorageFactories(): array
    {
        $cases = [
            'SingleProcessStorage' => [
                fn () => new SingleProcessStorage(),
            ],
            'SessionStorage' => [
                fn () => new SessionStorage('test'),
            ],
            'FileStorage' => [function () {
                vfsStream::setup('fileStorage');
                return new FileStorage(vfsStream::url('fileStorage/data'));
            }],
            'sqlite' => [function () {
                $pdo = new PDO('sqlite::memory:');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return new PDOStorage('test', $pdo);
            }],
        ];

        if (getenv('MYSQL_DSN')) {
            $cases['MYSQL'] = [function () {
                $pdo = new PDO(getenv('MYSQL_DSN'), getenv('MYSQL_USER'));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
                return new PDOStorage('test', $pdo);
            }];
        }
        if (getenv('PGSQL_DSN')) {
            $cases['PGSQL'] = [function () {
                $pdo = new PDO(getenv('PGSQL_DSN'), getenv('PGSQL_USER'));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return new PDOStorage('test', $pdo);
            }];
        }
        if (getenv('MEMCACHE_HOST')) {
            $cases['MemcachedStorage'] = [function () {
                $memcached = new Memcached();
                $memcached->addServer(getenv('MEMCACHE_HOST'), 11211);
                return new MemcachedStorage('test', $memcached);
            }];
        }
        if (getenv('REDIS_URI')) {
            $cases['PHPRedisStorage'] = [function () {
                $uri = parse_url(getenv('REDIS_URI'));
                $redis = new Redis();
                $redis->connect($uri['host']);
                return new PHPRedisStorage('test', $redis);
            }];

            $cases['PredisStorage'] = [function () {
                $redis = new Client(getenv('REDIS_URI'));
                return new PredisStorage('test', $redis);
            }];
        }
        return $cases;
    }

    /**
     * Tests setMicrotime() and getMicrotime().
     *
     * @param callable $storageFactory Returns a storage.
     */
    #[DataProvider('provideStorageFactories')]
    public function testSetAndGetMicrotime(callable $storageFactory): void
    {
        $this->storage = call_user_func($storageFactory);
        $this->storage->bootstrap(1);
        $this->storage->getMicrotime();

        $this->storage->setMicrotime(1.1);
        $this->assertSame(1.1, $this->storage->getMicrotime());
        $this->assertSame(1.1, $this->storage->getMicrotime());

        $this->storage->setMicrotime(1.2);
        $this->assertSame(1.2, $this->storage->getMicrotime());

        $this->storage->setMicrotime(1436551945.0192);
        $this->assertSame(1436551945.0192, $this->storage->getMicrotime());
    }

    /**
     * Tests isBootstrapped().
     *
     * @param callable $storageFactory Returns a storage.
     */
    #[DataProvider('provideStorageFactories')]
    public function testBootstrap(callable $storageFactory): void
    {
        $this->storage = call_user_func($storageFactory);

        $this->storage->bootstrap(123);
        $this->assertTrue($this->storage->isBootstrapped());
        $this->assertEquals(123, $this->storage->getMicrotime());
    }

    /**
     * Tests isBootstrapped().
     *
     * @param callable $storageFactory Returns a storage.
     */
    #[DataProvider('provideStorageFactories')]
    public function testIsBootstrapped(callable $storageFactory): void
    {
        $this->storage = call_user_func($storageFactory);
        $this->assertFalse($this->storage->isBootstrapped());

        $this->storage->bootstrap(123);
        $this->assertTrue($this->storage->isBootstrapped());

        $this->storage->remove();
        $this->assertFalse($this->storage->isBootstrapped());
    }

    /**
     * Tests remove().
     *
     * @param callable $storageFactory Returns a storage.
     */
    #[DataProvider('provideStorageFactories')]
    public function testRemove(callable $storageFactory): void
    {
        $this->storage = call_user_func($storageFactory);
        $this->storage->bootstrap(123);

        $this->storage->remove();
        $this->assertFalse($this->storage->isBootstrapped());
    }

    /**
     * When no tokens are available, the bucket should return false.
     *
     * @param callable $storageFactory Returns a storage.
     * @throws StorageException
     */
    #[DataProvider('provideStorageFactories')]
    public function testConsumingUnavailableTokensReturnsFalse(callable $storageFactory): void
    {
        $this->storage = call_user_func($storageFactory);
        $capacity = 10;
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket($capacity, $rate, $this->storage);
        $bucket->bootstrap(0);

        $this->assertFalse($bucket->consume(10));
    }

    /**
     * When tokens are available, the bucket should return true.
     *
     * @param callable $storageFactory Returns a storage.
     * @throws StorageException
     */
    #[DataProvider('provideStorageFactories')]
    public function testConsumingAvailableTokensReturnsTrue(callable $storageFactory): void
    {
        $this->storage = call_user_func($storageFactory);
        $capacity = 10;
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket($capacity, $rate, $this->storage);
        $bucket->bootstrap(10);

        $this->assertTrue($bucket->consume(10));
    }

    /**
     * Tests synchronized bootstrap
     *
     * @param callable $storageFactory Returns a storage.
     * @throws \Exception
     */
    #[DataProvider('provideStorageFactories')]
    public function testSynchronizedBootstrap(callable $storageFactory): void
    {
        $this->storage = call_user_func($storageFactory);
        $this->storage->getMutex()
            ->synchronized(function () {
                $this->assertFalse($this->storage->isBootstrapped());
                $this->storage->bootstrap(123);
                $this->assertTrue($this->storage->isBootstrapped());
            });
    }
}
