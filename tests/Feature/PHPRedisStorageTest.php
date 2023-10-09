<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use Closure;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisException;
use Securepoint\TokenBucket\Storage\PHPRedisStorage;
use Securepoint\TokenBucket\Storage\StorageException;

/**
 * Tests for PHPRedisStorage.
 *
 * These tests need the environment variable REDIS_URI.
 *
 * @license WTFPL
 * @see PHPRedisStorage
 */
class PHPRedisStorageTest extends TestCase
{
    /**
     * @var Redis The API.
     */
    private Redis $redis;

    /**
     * @var PHPRedisStorage The SUT.
     */
    private PHPRedisStorage $storage;

    protected function setUp(): void
    {
        if (! getenv('REDIS_URI')) {
            throw new Exception('Define REDIS_URI env var!');
        }
        /** @var array{"scheme": string, "host": string, "port":int, "user": string, "pass": string, "query": string, "path": string, "fragment": string} $uri */
        $uri = parse_url(getenv('REDIS_URI'));
        $this->redis = new Redis();
        $this->redis->connect($uri['host']);

        $this->storage = new PHPRedisStorage('test', $this->redis);
    }

    /**
     * Tests broken server communication.
     *
     * @param callable $method The tested method.
     * @throws RedisException
     */
    #[DataProvider('provideTestBrokenCommunication')]
    public function testBrokenCommunication(callable $method): void
    {
        if (! getenv('REDIS_URI')) {
            throw new Exception('Define REDIS_URI env var!');
        }
        /** @var array{"scheme": string, "host": string, "port":int, "user": string, "pass": string, "query": string, "path": string, "fragment": string} $uri */
        $uri = parse_url(getenv('REDIS_URI'));
        $redis = new Redis();
        try {
            $redis->setOption(Redis::OPT_MAX_RETRIES, 0);
            $redis->connect($uri['host']);
        } catch (RedisException) {
        }
        $storage = new PHPRedisStorage('test', $redis);

        $this->expectException(StorageException::class);
        $this->redis->close();
        call_user_func($method, $storage);
    }

    /**
     * Provides test cases for testBrokenCommunication().
     *
     * @return array<array<Closure>> Testcases.
     */
    public static function provideTestBrokenCommunication(): array
    {
        return [
            [function (PHPRedisStorage $storage) {
                $storage->bootstrap(1);
            }],
            [function (PHPRedisStorage $storage) {
                $storage->isBootstrapped();
            }],
            [function (PHPRedisStorage $storage) {
                $storage->remove();
            }],
            [function (PHPRedisStorage $storage) {
                $storage->setMicrotime(1);
            }],
            [function (PHPRedisStorage $storage) {
                $storage->getMicrotime();
            }],
        ];
    }

    /**
     * Tests remove() fails.
     */
    public function testRemoveFails(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->remove();
    }

    /**
     * Tests setMicrotime() fails.
     */
    public function testSetMicrotimeFails(): void
    {
        $this->expectException(StorageException::class);
        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('set')
            ->willThrowException(new RedisException('error'));
        $storage = new PHPRedisStorage('test', $redis);
        $storage->setMicrotime(1);
    }

    /**
     * Tests getMicrotime() fails.
     */
    public function testGetMicrotimeFails(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->getMicrotime();
    }
}
