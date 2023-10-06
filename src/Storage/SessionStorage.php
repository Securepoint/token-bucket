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
 * @license WTFPL
 */
final class SessionStorage implements Storage, SessionScope
{
    /**
     * @internal
     */
    public const SESSION_NAMESPACE = 'token_bucket_';

    /**
     * @var NoMutex The mutex.
     */
    private readonly NoMutex $mutex;

    /**
     * @var string The session key for this bucket.
     */
    private readonly string $key;

    /**
     * Sets the bucket's name.
     *
     * @param string $name The bucket's name.
     */
    public function __construct(string $name)
    {
        $this->mutex = new NoMutex();
        $this->key = self::SESSION_NAMESPACE . $name;
    }

    public function getMutex(): Mutex
    {
        return $this->mutex;
    }

    public function bootstrap(float $microtime): void
    {
        $this->setMicrotime($microtime);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function getMicrotime(): float
    {
        return is_float($_SESSION[$this->key]) ? $_SESSION[$this->key] : 0.0;
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function isBootstrapped(): bool
    {
        return isset($_SESSION[$this->key]);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function remove(): void
    {
        unset($_SESSION[$this->key]);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function setMicrotime(float $microtime): void
    {
        $_SESSION[$this->key] = $microtime;
    }

    public function letMicrotimeUnchanged(): void
    {
    }
}
