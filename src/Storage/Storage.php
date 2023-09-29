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
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
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
    public function getMutex();

    /**
     * Returns if the Storage was already bootstrapped.
     *
     * @return bool True if the Storage was already bootstrapped.
     * @throws StorageException Checking the state of the Storage failed.
     * @internal
     */
    public function isBootstrapped();

    /**
     * Bootstraps the Storage.
     *
     * @param double $microtime The timestamp.
     * @throws StorageException Bootstrapping failed.
     * @internal
     */
    public function bootstrap($microtime);

    /**
     * Removes the Storage.
     *
     * After a Storage was removed you should not use that object anymore.
     * The only defined methods after that operations are isBootstrapped()
     * and bootstrap(). A call to bootstrap() results in a defined object
     * again.
     *
     * @throws StorageException Cleaning failed.
     * @internal
     */
    public function remove();

    /**
     * Stores a timestamp.
     *
     * @param double $microtime The timestamp.
     * @throws StorageException Writing to the Storage failed.
     * @internal
     */
    public function setMicrotime($microtime);

    /**
     * Indicates, that there won't be any change within this transaction.
     *
     * @internal
     */
    public function letMicrotimeUnchanged();

    /**
     * Returns the stored timestamp.
     *
     * @return double The timestamp.
     * @throws StorageException Reading from the Storage failed.
     * @internal
     */
    public function getMicrotime();
}
