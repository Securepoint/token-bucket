<?php

use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\MemcachedStorage;

/**
 * Tests for MemcachedStorage.
 *
 * These tests need the environment variable MEMCACHE_HOST.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see MemcachedStorage
 */
class MemcachedStorageTest extends TestCase
{

    /**
     * @var \Memcached The memcached API.
     */
    private $memcached;

    /**
     * @var MemcachedStorage The SUT.
     */
    private $storage;
    
    protected function setUp(): void
    {
        parent::setUp();

        if (!getenv("MEMCACHE_HOST")) {
            $this->markTestSkipped();
            return;
        }
        $this->memcached = new \Memcached();
        $this->memcached->addServer(getenv("MEMCACHE_HOST"), 11211);

        $this->storage = new MemcachedStorage("test", $this->memcached);
        $this->storage->bootstrap(123);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        if (!getenv("MEMCACHE_HOST")) {
            return;
        }
        $memcached = new \Memcached();
        $memcached->addServer(getenv("MEMCACHE_HOST"), 11211);
        $memcached->flush();
    }

    /**
     * Tests bootstrap() returns silenty if the key exists already.
     *
     * @test
     */
    public function testBootstrapReturnsSilentlyIfKeyExists()
    {
        $this->storage->bootstrap(234);
    }

    /**
     * Tests bootstrap() fails.
     *
     * @test
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testBootstrapFails()
    {
        $storage = new MemcachedStorage("test", new \Memcached());
        $storage->bootstrap(123);
    }

    /**
     * Tests isBootstrapped() fails
     *
     * @test
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testIsBootstrappedFails()
    {
        $this->markTestIncomplete();
    }

    /**
     * Tests remove() fails
     *
     * @test
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testRemoveFails()
    {
        $storage = new MemcachedStorage("test", new \Memcached());
        $storage->remove();
    }

    /**
     * Tests setMicrotime() fails if getMicrotime() wasn't called first.
     *
     * @test
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testSetMicrotimeFailsIfGetMicrotimeNotCalledFirst()
    {
        $this->storage->setMicrotime(123);
    }

    /**
     * Tests setMicrotime() fails.
     *
     * @test
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testSetMicrotimeFails()
    {
        $this->storage->getMicrotime();
        $this->memcached->resetServerList();
        $this->storage->setMicrotime(123);
    }
    
    /**
     * Tests setMicrotime() returns silenty if the cas operation failed.
     *
     * @test
     */
    public function testSetMicrotimeReturnsSilentlyIfCASFailed()
    {
        // acquire cas token
        $this->storage->getMicrotime();
        
        // invalidate the cas token
        $storage2 = new MemcachedStorage("test", $this->memcached);
        $storage2->getMicrotime();
        $storage2->setMicrotime(234);
        
        $this->storage->setMicrotime(123);
    }
    
    
    /**
     * Tests getMicrotime() fails.
     *
     * @test
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testGetMicrotimeFails()
    {
        $storage = new MemcachedStorage("test", new \Memcached());
        $storage->getMicrotime();
    }
}