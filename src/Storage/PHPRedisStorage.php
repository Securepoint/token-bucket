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
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 */
final class PHPRedisStorage implements Storage, GlobalScope
{
    /**
     * @var Mutex The mutex.
     */
    private $mutex;

    /**
     * @var Redis The redis API.
     */
    private $redis;

    /**
     * @var string The key.
     */
    private $key;

    /**
     * Sets the connected Redis API.
     *
     * The Redis API needs to be connected yet. I.e. Redis::connect() was
     * called already.
     *
     * @param string $name  The resource name.
     * @param Redis  $redis The Redis API.
     */
    public function __construct($name, Redis $redis)
    {
        $this->key = $name;
        $this->redis = $redis;
        $this->mutex = new PHPRedisMutex([$redis], $name);
    }

    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }

    public function isBootstrapped()
    {
        try {
            return $this->redis->exists($this->key);
        } catch (RedisException $e) {
            throw new StorageException('Failed to check for key existence', 0, $e);
        }
    }

    public function remove()
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
    public function setMicrotime($microtime)
    {
        try {
            $data = DoublePacker::pack($microtime);

            if (! $this->redis->set($this->key, $data)) {
                throw new StorageException('Failed to store microtime');
            }
        } catch (RedisException $e) {
            throw new StorageException('Failed to store microtime', 0, $e);
        }
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function getMicrotime()
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

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
