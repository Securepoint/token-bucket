<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use malkusch\lock\mutex\Mutex;
use malkusch\lock\mutex\NoMutex;
use Securepoint\TokenBucket\Storage\Scope\SessionScope;

/**
 * Session based storage which is shared for one user accross requests.
 *
 * This storage is in the session scope.
 *
 * As PHP's session are thread safe this implementation doesn't provide a
 * locking Mutex.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 */
final class SessionStorage implements Storage, SessionScope
{
    /**
     * @var Mutex The mutex.
     */
    private $mutex;

    /**
     * @var string The session key for this bucket.
     */
    private $key;

    /**
     * @internal
     */
    public const SESSION_NAMESPACE = 'TokenBucket_';

    /**
     * Sets the bucket's name.
     *
     * @param string $name The bucket's name.
     */
    public function __construct($name)
    {
        $this->mutex = new NoMutex();
        $this->key = self::SESSION_NAMESPACE . $name;
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function getMicrotime()
    {
        return $_SESSION[$this->key];
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function isBootstrapped()
    {
        return isset($_SESSION[$this->key]);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function remove()
    {
        unset($_SESSION[$this->key]);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function setMicrotime($microtime)
    {
        $_SESSION[$this->key] = $microtime;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
