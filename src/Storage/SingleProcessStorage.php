<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use malkusch\lock\mutex\Mutex;
use malkusch\lock\mutex\NoMutex;
use Securepoint\TokenBucket\Storage\Scope\RequestScope;

/**
 * In-memory token storage which is only used for one single process.
 *
 * This storage is in the request scope. It is not shared among processes and
 * therefore needs no locking.
 *
 * @license WTFPL
 */
final class SingleProcessStorage implements Storage, RequestScope
{
    /**
     * @var NoMutex The mutex.
     */
    private readonly NoMutex $mutex;

    /**
     * @var double|null The microtime.
     */
    private ?float $microtime = null;

    /**
     * Initialization.
     */
    public function __construct()
    {
        $this->mutex = new NoMutex();
    }

    public function isBootstrapped(): bool
    {
        return $this->microtime !== null;
    }

    public function bootstrap(float $microtime): void
    {
        $this->setMicrotime($microtime);
    }

    public function remove(): void
    {
        $this->microtime = null;
    }

    public function setMicrotime(float $microtime): void
    {
        $this->microtime = $microtime;
    }

    public function getMicrotime(): float
    {
        return $this->microtime ?? 0;
    }

    public function getMutex(): Mutex
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged(): void
    {
    }
}
