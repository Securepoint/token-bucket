<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use InvalidArgumentException;
use malkusch\lock\mutex\Mutex;
use malkusch\lock\mutex\SemaphoreMutex;
use Securepoint\TokenBucket\Storage\Scope\GlobalScope;
use Securepoint\TokenBucket\Util\DoublePacker;
use SysvSemaphore;
use SysvSharedMemory;

/**
 * Shared memory based storage which can be shared among processes of a single host.
 *
 * This storage is in the global scope. However the scope is limited to the
 * shared memory. I.e. the scope is not shared between hosts.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 */
final class IPCStorage implements Storage, GlobalScope
{
    /**
     * @var Mutex The mutex.
     */
    private $mutex;

    /**
     * @var int The System V IPC key.
     */
    private int $key;

    /**
     * @var resource The shared memory.
     */
    private ?SysvSharedMemory $memory;

    /**
     * @var resource The semaphore id.
     */
    private ?SysvSemaphore $semaphore;

    /**
     * Sets the System V IPC key for the shared memory and its semaphore.
     *
     * You can create the key with PHP's function ftok().
     *
     * @param int $key The System V IPC key.
     *
     * @throws StorageException Could initialize IPC infrastructure.
     */
    public function __construct(int $key)
    {
        $this->key = $key;
        $this->attach();
    }

    /**
     * Attaches the shared memory segment.
     *
     * @throws StorageException Could not initialize IPC infrastructure.
     */
    private function attach()
    {
        try {
            $this->semaphore = sem_get($this->key);
            $this->mutex = new SemaphoreMutex($this->semaphore);
        } catch (InvalidArgumentException $e) {
            throw new StorageException('Could not get semaphore id.', 0, $e);
        }

        if (!$this->memory = shm_attach($this->key, 128)) {
            throw new StorageException('Failed to attach to shared memory.');
        }
    }

    public function bootstrap($microtime)
    {
        if ($this->memory === null) {
            $this->attach();
        }
        $this->setMicrotime($microtime);
    }

    public function isBootstrapped()
    {
        return $this->memory !== null && shm_has_var($this->memory, 0);
    }

    public function remove()
    {
        if ($this->memory !== null) {
            shm_remove($this->memory);
            $this->memory = null;
        }

        if ($this->semaphore !== null) {
            sem_remove($this->semaphore);
            $this->semaphore = null;
        }
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function setMicrotime($microtime)
    {
        if ($this->memory === null) {
            throw new StorageException('No shared memory initialized.');
        }
        $data = DoublePacker::pack($microtime);
        shm_put_var($this->memory, 0, $data);
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function getMicrotime()
    {
        $data = shm_get_var($this->memory, 0);
        if ($data === false) {
            throw new StorageException('Could not read from shared memory.');
        }
        return DoublePacker::unpack($data);
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
