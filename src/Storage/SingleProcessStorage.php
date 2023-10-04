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
 * @author Markus Malkusch <markus@malkusch.de>
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
    private $microtime;

    /**
     * Initialization.
     */
    public function __construct()
    {
        $this->mutex = new NoMutex();
    }

    public function isBootstrapped()
    {
        return $this->microtime !== null;
    }

    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }

    public function remove()
    {
        $this->microtime = null;
    }

    public function setMicrotime($microtime)
    {
        $this->microtime = $microtime;
    }

    public function getMicrotime()
    {
        return $this->microtime;
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
