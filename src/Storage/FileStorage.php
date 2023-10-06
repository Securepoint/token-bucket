<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use malkusch\lock\mutex\FlockMutex;
use malkusch\lock\mutex\Mutex;
use Securepoint\TokenBucket\Storage\Scope\GlobalScope;
use Securepoint\TokenBucket\Util\DoublePacker;

/**
 * File based storage which can be shared among processes.
 *
 * This storage is in the global scope. However the scope is limited to the
 * underlying filesystem. I.e. the scope is not shared between hosts.
 *
 * @license WTFPL
 */
final class FileStorage implements Storage, GlobalScope
{
    /**
     * @var FlockMutex The mutex.
     */
    private FlockMutex $mutex;

    /**
     * @var resource|false The file handle.
     */
    private $fileHandle;

    /**
     * Sets the file path and opens it.
     *
     * If the file does not exist yet, it will be created. This is an atomic
     * operation.
     *
     * @param string $path The file path.
     * @throws StorageException
     */
    public function __construct(
        private readonly string $path
    ) {
        $this->open();
    }

    /**
     * Closes the file handle.
     *
     * @throws StorageException
     * @internal
     */
    public function __destruct()
    {
        if (! $this->fileHandle) {
            throw new StorageException('No file handle opened');
        }

        fclose($this->fileHandle);
    }

    /**
     * @throws StorageException
     */
    public function isBootstrapped(): bool
    {
        if (! $this->fileHandle) {
            throw new StorageException('No file handle opened');
        }

        /** @var array{size:int} $stats */
        $stats = fstat($this->fileHandle);
        return $stats['size'] > 0;
    }


    /**
     * @throws StorageException
     */
    public function bootstrap(float $microtime): void
    {
        $this->open(); // remove() could have deleted the file.
        $this->setMicrotime($microtime);
    }


    /**
     * @throws StorageException
     */
    public function remove(): void
    {
        if (! $this->fileHandle) {
            throw new StorageException('No file handle opened');
        }

        // Truncate to notify isBootstrapped() about the new state.
        if (! ftruncate($this->fileHandle, 0)) {
            throw new StorageException("Could not truncate {$this->path}");
        }
        if (! unlink($this->path)) {
            throw new StorageException("Could not delete {$this->path}");
        }
    }


    /**
     * @throws StorageException
     */
    public function setMicrotime(float $microtime): void
    {
        if (! $this->fileHandle) {
            throw new StorageException('No file handle opened');
        }

        if (fseek($this->fileHandle, 0) !== 0) {
            throw new StorageException('Could not move to beginning of the file.');
        }

        $data = DoublePacker::pack($microtime);
        $result = fwrite($this->fileHandle, $data, strlen($data));
        if ($result !== strlen($data)) {
            throw new StorageException('Could not write to storage.');
        }
    }


    /**
     * @throws StorageException
     */
    public function getMicrotime(): float
    {
        if (! $this->fileHandle) {
            throw new StorageException('No file handle opened');
        }

        if (fseek($this->fileHandle, 0) !== 0) {
            throw new StorageException('Could not move to beginning of the file.');
        }
        $data = fread($this->fileHandle, 8);
        if ($data === false) {
            throw new StorageException('Could not read from storage.');
        }

        return DoublePacker::unpack($data);
    }

    public function getMutex(): Mutex
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged(): void
    {
    }

    /**
     * Opens the file and initializes the mutex.
     */
    private function open(): void
    {
        $this->fileHandle = fopen($this->path, 'c+');
        if (! $this->fileHandle) {
            throw new StorageException("Could not open '{$this->path}'.");
        }
        $this->mutex = new FlockMutex($this->fileHandle);
    }
}
