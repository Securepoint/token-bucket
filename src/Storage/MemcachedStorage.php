<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use malkusch\lock\mutex\CASMutex;
use malkusch\lock\mutex\Mutex;
use Memcached;
use Securepoint\TokenBucket\Storage\Scope\GlobalScope;

/**
 * Memcached based storage which can be shared among processes.
 *
 * This storage is in the global scope.
 *
 * @license WTFPL
 */
final class MemcachedStorage implements Storage, GlobalScope
{
    /**
     * @internal
     */
    public const PREFIX = 'TokenBucketD_';

    /**
     * @var float The CAS token.
     */
    private $casToken;

    /**
     * @var string The key for the token bucket.
     */
    private readonly string $key;

    /**
     * @var CASMutex The mutex for this storage.
     */
    private readonly CASMutex $mutex;

    /**
     * Sets the memcached API and the token bucket name.
     *
     * The api needs to have at least one server in its pool. I.e.
     * it has to be added with Memcached::addServer().
     *
     * @param string     $name      The name of the shared token bucket.
     * @param Memcached $memcached The memcached API.
     */
    public function __construct(
        string $name,
        private readonly Memcached $memcached
    ) {
        $this->key = self::PREFIX . $name;
        $this->mutex = new CASMutex();
    }

    public function bootstrap(float $microtime): void
    {
        if ($this->memcached->add($this->key, $microtime)) {
            $this->mutex->notify(); // [CAS] Stop TokenBucket::bootstrap()
            return;
        }
        if ($this->memcached->getResultCode() === Memcached::RES_NOTSTORED) {
            // [CAS] repeat TokenBucket::bootstrap()
            return;
        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }

    public function isBootstrapped(): bool
    {
        if ($this->memcached->get($this->key) !== false) {
            $this->mutex->notify(); // [CAS] Stop TokenBucket::bootstrap()
            return true;
        }
        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return false;
        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }

    public function remove(): void
    {
        if (! $this->memcached->delete($this->key)) {
            throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }
    }

    public function setMicrotime(float $microtime): void
    {
        if ($this->casToken === null) {
            throw new StorageException('CAS token is null. Call getMicrotime() first.');
        }
        if ($this->memcached->cas($this->casToken, $this->key, $microtime)) {
            $this->mutex->notify(); // [CAS] Stop TokenBucket::consume()
            return;
        }
        if ($this->memcached->getResultCode() === Memcached::RES_DATA_EXISTS) {
            // [CAS] repeat TokenBucket::consume()
            return;
        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }

    public function getMicrotime(): float
    {
        $getDelayed = $this->memcached->getDelayed([$this->key], true);
        if (! $getDelayed) {
            throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }

        $result = $this->memcached->fetchAll();
        if (! $result) {
            throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }

        $microtime = $result[0]['value'];
        $this->casToken = $result[0]['cas'];
        if ($this->casToken === null) {
            throw new StorageException('Failed to aquire a CAS token.');
        }

        return (float) $microtime;
    }

    public function getMutex(): Mutex
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged(): void
    {
        $this->mutex->notify();
    }
}
