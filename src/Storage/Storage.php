<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use malkusch\lock\mutex\Mutex;
use Securepoint\TokenBucket\Storage\Scope\GlobalScope;
use Securepoint\TokenBucket\Storage\Scope\RequestScope;
use Securepoint\TokenBucket\Storage\Scope\SessionScope;

/**
 * Token Storage.
 *
 * The storage determines the scope for the token bucket. It therefore
 * implements one of the *Scope marker interfaces:
 *
 * - {@link RequestScope}: The bucket is shared only within one request.
 * - {@link SessionScope}: The bucket is shared between requests of one session.
 * - {@link GlobalScope}: The bucket is shared among all processes.
 *
 * @license WTFPL
 */
interface Storage
{
    /**
     * Returns the Mutex for this Storage.
     *
     * @return Mutex The mutex.
     * @internal
     */
    public function getMutex(): Mutex;

    /**
     * Returns if the Storage was already bootstrapped.
     *
     * @return bool True if the Storage was already bootstrapped.
     * @internal
     */
    public function isBootstrapped(): bool;

    /**
     * Bootstraps the Storage.
     *
     * @param float $microtime The timestamp.
     * @internal
     */
    public function bootstrap(float $microtime): void;

    /**
     * Removes the Storage.
     *
     * After a Storage was removed you should not use that object anymore.
     * The only defined methods after that operations are isBootstrapped()
     * and bootstrap(). A call to bootstrap() results in a defined object
     * again.
     *
     * @internal
     */
    public function remove(): void;

    /**
     * Stores a timestamp.
     *
     * @param float $microtime The timestamp.
     * @internal
     */
    public function setMicrotime(float $microtime): void;

    /**
     * Indicates, that there won't be any change within this transaction.
     *
     * @internal
     */
    public function letMicrotimeUnchanged(): void;

    /**
     * Returns the stored timestamp.
     *
     * @return float The timestamp.
     * @internal
     */
    public function getMicrotime(): float;
}
