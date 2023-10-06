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
use Securepoint\TokenBucket\Storage\FileStorage;
use Securepoint\TokenBucket\Storage\MemcachedStorage;
use Securepoint\TokenBucket\Storage\PDOStorage;
use Securepoint\TokenBucket\Storage\PHPRedisStorage;
use Securepoint\TokenBucket\Storage\PredisStorage;
use Securepoint\TokenBucket\Storage\SessionStorage;
use Securepoint\TokenBucket\Storage\StorageException;

/**
 * Tests for shared Storage implementations.
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
class SharedStorageTest extends TestCase
{
    private array $storages = [];

    protected function tearDown(): void
    {
        foreach ($this->storages as $storage) {
            try {
                @$storage->remove();
            } catch (StorageException) {
                // ignore missing vfsStream files.
            }
        }
    }

    /**
     * Provides shared Storage implementations.
     *
     * @return callable[][] Storage factories.
     */
    public static function provideStorageFactories()
    {
        $cases = [
            [
                fn ($name) => new SessionStorage($name),
            ],

            [function ($name) {
                vfsStream::setup('fileStorage');
                return new FileStorage(vfsStream::url("fileStorage/{$name}"));
            }],

            [function ($name) {
                $pdo = new PDO('sqlite::memory:');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return new PDOStorage($name, $pdo);
            }],
        ];

        if (getenv('MYSQL_DSN')) {
            $cases[] = [function ($name) {
                $pdo = new PDO(getenv('MYSQL_DSN'), getenv('MYSQL_USER'));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false);

                $storage = new PDOStorage($name, $pdo);

                $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);

                return $storage;
            }];
        }
        if (getenv('PGSQL_DSN')) {
            $cases[] = [function ($name) {
                $pdo = new PDO(getenv('PGSQL_DSN'), getenv('PGSQL_USER'));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return new PDOStorage($name, $pdo);
            }];
        }
        if (getenv('MEMCACHE_HOST')) {
            $cases[] = [function ($name) {
                $memcached = new Memcached();
                $memcached->addServer(getenv('MEMCACHE_HOST'), 11211);
                return new MemcachedStorage($name, $memcached);
            }];
        }
        if (getenv('REDIS_URI')) {
            $cases['PHPRedisStorage'] = [function ($name) {
                $uri = parse_url(getenv('REDIS_URI'));
                $redis = new Redis();
                $redis->connect($uri['host']);
                return new PHPRedisStorage($name, $redis);
            }];

            $cases['PredisStorage'] = [function ($name) {
                $redis = new Client(getenv('REDIS_URI'));
                return new PredisStorage($name, $redis);
            }];
        }
        return $cases;
    }

    /**
     * Tests two storages with different names don't interfere each other.
     *
     * @param callable $factory The Storage factory.
     */
    #[DataProvider('provideStorageFactories')]
    public function testStoragesDontInterfere(callable $factory)
    {
        $storageA = call_user_func($factory, 'A');
        $storageA->bootstrap(0);
        $storageA->getMicrotime();
        $this->storages[] = $storageA;

        $storageB = call_user_func($factory, 'B');
        $storageB->bootstrap(0);
        $storageB->getMicrotime();
        $this->storages[] = $storageB;

        $storageA->setMicrotime(1);
        $storageB->setMicrotime(2);

        $this->assertNotEquals($storageA->getMicrotime(), $storageB->getMicrotime());
    }
}
