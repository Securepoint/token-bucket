<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\IPCStorage;
use Securepoint\TokenBucket\Storage\StorageException;
use TypeError;

/**
 * Tests for IPCStorage.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @see IPCStorage
 */
class IPCStorageTest extends TestCase
{
    /**
     * Tests remove() fails.
     */
    public function testRemoveFails()
    {
        $this->expectNotToPerformAssertions();
        $storage = new IPCStorage(ftok(__FILE__, 'a'));
        $storage->remove();
        $storage->remove();
    }

    /**
     * Tests removing semaphore fails.
     */
    public function testfailRemovingSemaphore()
    {
        $this->expectNotToPerformAssertions();
        $key = ftok(__FILE__, 'a');
        $storage = new IPCStorage($key);

        sem_remove(sem_get($key));
        $storage->remove();
    }

    /**
     * Tests setMicrotime() fails.
     */
    public function testSetMicrotimeFails()
    {
        $this->expectException(StorageException::class);
        $storage = new IPCStorage(ftok(__FILE__, 'a'));
        $storage->remove();
        @$storage->setMicrotime(123);
    }

    /**
     * Tests getMicrotime() fails.
     */
    public function testGetMicrotimeFails()
    {
        $this->expectException(StorageException::class);
        $storage = new IPCStorage(ftok(__FILE__, 'b'));
        @$storage->getMicrotime();
    }
}
