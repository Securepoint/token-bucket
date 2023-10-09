<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use Exception;
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
            throw new Exception('Define memcache host!');
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
            throw new Exception('Define memcache host!');
        }
        $memcached = new Memcached();
        $memcached->addServer(getenv('MEMCACHE_HOST'), 11211);
        $memcached->flush();
    }

    /**
     * Tests bootstrap() returns silenty if the key exists already.
     */
    public function testBootstrapReturnsSilentlyIfKeyExists(): void
    {
        $this->expectNotToPerformAssertions();
        $this->storage->bootstrap(234);
    }

    /**
     * Tests bootstrap() fails.
     */
    public function testBootstrapFails(): void
    {
        $this->expectException(StorageException::class);
        $storage = new MemcachedStorage('test', new Memcached());
        $storage->bootstrap(123);
    }

    /**
     * Tests isBootstrapped() fails
     */
    public function testIsBootstrappedFails(): void
    {
        $this->expectException(StorageException::class);
        $memcached = new Memcached();
        $memcached->addServer(getenv('MEMCACHE_HOST'), 11211);
        $storage = new MemcachedStorage('test', new Memcached());
        $storage->bootstrap(123);
        $storage->remove();
        $storage->isBootstrapped();
    }

    /**
     * Tests remove() fails
     */
    public function testRemoveFails(): void
    {
        $this->expectException(StorageException::class);
        $storage = new MemcachedStorage('test', new Memcached());
        $storage->remove();
    }

    /**
     * Tests setMicrotime() fails if getMicrotime() wasn't called first.
     */
    public function testSetMicrotimeFailsIfGetMicrotimeNotCalledFirst(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->setMicrotime(123);
    }

    /**
     * Tests setMicrotime() fails.
     */
    public function testSetMicrotimeFails(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->getMicrotime();
        $this->memcached->resetServerList();
        $this->storage->setMicrotime(123);
    }

    /**
     * Tests setMicrotime() returns silenty if the cas operation failed.
     * @throws StorageException
     */
    public function testSetMicrotimeReturnsSilentlyIfCASFailed(): void
    {
        $this->expectNotToPerformAssertions();

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
    public function testGetMicrotimeFails(): void
    {
        $this->expectException(StorageException::class);
        $storage = new MemcachedStorage('test', new Memcached());
        $storage->getMicrotime();
    }
}
