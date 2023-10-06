<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use malkusch\lock\mutex\Mutex;
use malkusch\lock\mutex\PredisMutex;
use Predis\Client;
use Predis\PredisException;
use Predis\Response\ErrorInterface;
use Securepoint\TokenBucket\Storage\Scope\GlobalScope;
use Securepoint\TokenBucket\Util\DoublePacker;

/**
 * Redis based storage which uses the Predis API.
 *
 * This storage is in the global scope.
 *
 * @license WTFPL
 */
final class PredisStorage implements Storage, GlobalScope
{
    /**
     * @var PredisMutex The mutex.
     */
    private readonly PredisMutex $mutex;

    /**
     * Sets the Redis API.
     *
     * @param string $key The resource name.
     * @param Client $redis The Redis API.
     */
    public function __construct(
        private readonly string $key,
        private readonly Client $redis
    ) {
        $this->mutex = new PredisMutex([$redis], $key);
    }

    public function bootstrap(float $microtime): void
    {
        $this->setMicrotime($microtime);
    }

    public function isBootstrapped(): bool
    {
        try {
            return (bool) $this->redis->exists($this->key);
        } catch (PredisException $e) {
            throw new StorageException('Failed to check for key existence', 0, $e);
        }
    }

    public function remove(): void
    {
        try {
            if (! $this->redis->del($this->key)) {
                throw new StorageException('Failed to delete key');
            }
        } catch (PredisException $e) {
            throw new StorageException('Failed to delete key', 0, $e);
        }
    }

    /**
     * @throws StorageException
     */
    public function setMicrotime(float $microtime): void
    {
        try {
            $data = DoublePacker::pack($microtime);
            if ($this->redis->set($this->key, $data) instanceof ErrorInterface) {
                throw new StorageException('Failed to store microtime');
            }
        } catch (PredisException $e) {
            throw new StorageException('Failed to store microtime', 0, $e);
        }
    }

    /**
     * @throws StorageException
     */
    public function getMicrotime(): float
    {
        try {
            $data = $this->redis->get($this->key);
            if ($data === null) {
                throw new StorageException('Failed to get microtime');
            }
            return DoublePacker::unpack($data);
        } catch (PredisException $e) {
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
