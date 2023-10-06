<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use Memcached;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\MemcachedStorage;
use Securepoint\TokenBucket\Storage\StorageException;

/**
 * Tests for MemcachedStorage.
 *
 * These tests need the environment variable MEMCACHE_HOST.
 *
 * @license WTFPL
 * @see MemcachedStorage
 */
class MemcachedStorageTest extends TestCase
{
    /**
     * @var Memcached The memcached API.
     */
    private Memcached $memcached;

    /**
     * @var MemcachedStorage The SUT.
     */
    private MemcachedStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('MEMCACHE_HOST')) {
            $this->markTestSkipped();
        }
        $this->memcached = new Memcached();
        $this->memcached->addServer(getenv('MEMCACHE_HOST'), 11211);

        $this->storage = new MemcachedStorage('test', $this->memcached);
        $this->storage->bootstrap(123);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (! getenv('MEMCACHE_HOST')) {
            return;
        }
        $memcached = new Memcached();
        $memcached->addServer(getenv('MEMCACHE_HOST'), 11211);
        $memcached->flush();
    }

    /**
     * Tests bootstrap() returns silenty if the key exists already.
     */
    public function testBootstrapReturnsSilentlyIfKeyExists()
    {
        $this->storage->bootstrap(234);
    }

    /**
     * Tests bootstrap() fails.
     */
    public function testBootstrapFails()
    {
        $this->expectException(StorageException::class);
        $storage = new MemcachedStorage('test', new Memcached());
        $storage->bootstrap(123);
    }

    /**
     * Tests isBootstrapped() fails
     */
    public function testIsBootstrappedFails(): never
    {
        $this->expectException(StorageException::class);
        $this->markTestIncomplete();
    }

    /**
     * Tests remove() fails
     */
    public function testRemoveFails()
    {
        $this->expectException(StorageException::class);
        $storage = new MemcachedStorage('test', new Memcached());
        $storage->remove();
    }

    /**
     * Tests setMicrotime() fails if getMicrotime() wasn't called first.
     */
    public function testSetMicrotimeFailsIfGetMicrotimeNotCalledFirst()
    {
        $this->expectException(StorageException::class);
        $this->storage->setMicrotime(123);
    }

    /**
     * Tests setMicrotime() fails.
     */
    public function testSetMicrotimeFails()
    {
        $this->expectException(StorageException::class);
        $this->storage->getMicrotime();
        $this->memcached->resetServerList();
        $this->storage->setMicrotime(123);
    }

    /**
     * Tests setMicrotime() returns silenty if the cas operation failed.
     */
    public function testSetMicrotimeReturnsSilentlyIfCASFailed()
    {
        // acquire cas token
        $this->storage->getMicrotime();

        // invalidate the cas token
        $storage2 = new MemcachedStorage('test', $this->memcached);
        $storage2->getMicrotime();
        $storage2->setMicrotime(234);

        $this->storage->setMicrotime(123);
    }

    /**
     * Tests getMicrotime() fails.
     */
    public function testGetMicrotimeFails()
    {
        $this->expectException(StorageException::class);
        $storage = new MemcachedStorage('test', new Memcached());
        $storage->getMicrotime();
    }
}
