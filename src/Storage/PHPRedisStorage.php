<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use malkusch\lock\mutex\Mutex;
use malkusch\lock\mutex\PHPRedisMutex;
use Redis;
use RedisException;
use Securepoint\TokenBucket\Storage\Scope\GlobalScope;
use Securepoint\TokenBucket\Util\DoublePacker;

/**
 * Redis based storage which uses the phpredis extension.
 *
 * This storage is in the global scope.
 *
 * This implementation requires at least phpredis-2.2.4.
 *
 * @license WTFPL
 */
final class PHPRedisStorage implements Storage, GlobalScope
{
    /**
     * @var PHPRedisMutex The mutex.
     */
    private readonly PHPRedisMutex $mutex;

    /**
     * Sets the connected Redis API.
     *
     * The Redis API needs to be connected yet. I.e. Redis::connect() was
     * called already.
     *
     * @param string $key The resource name.
     * @param Redis  $redis The Redis API.
     */
    public function __construct(
        private readonly string $key,
        private readonly Redis $redis
    ) {
        $this->mutex = new PHPRedisMutex([$redis], $key);
    }

    /**
     * @throws StorageException
     */
    public function bootstrap(float $microtime): void
    {
        $this->setMicrotime($microtime);
    }

    public function isBootstrapped(): bool
    {
        try {
            return $this->redis->exists($this->key) === 1;
        } catch (RedisException $e) {
            throw new StorageException('Failed to check for key existence', 0, $e);
        }
    }

    public function remove(): void
    {
        try {
            if (! $this->redis->del($this->key)) {
                throw new StorageException('Failed to delete key');
            }
        } catch (RedisException $e) {
            throw new StorageException('Failed to delete key', 0, $e);
        }
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function setMicrotime(float $microtime): void
    {
        try {
            $data = DoublePacker::pack($microtime);

            if ($this->redis->set($this->key, $data) !== true) {
                throw new StorageException('Failed to store microtime');
            }
        } catch (RedisException $e) {
            throw new StorageException('Failed to store microtime', 0, $e);
        }
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function getMicrotime(): float
    {
        try {
            $data = $this->redis->get($this->key);
            if ($data === false) {
                throw new StorageException('Failed to get microtime');
            }
            return DoublePacker::unpack($data);
        } catch (RedisException $e) {
            throw new StorageException('Failed to get microtime', 0, $e);
        }
    }

    public function getMutex(): Mutex
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged(): void
    {
    }
}
