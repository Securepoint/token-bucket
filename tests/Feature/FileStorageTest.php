<?php

use phpmock\phpunit\PHPMock;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\FileStorage;

/**
 * Tests for FileStorage.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see FileStorage
 */
class FileStorageTest extends TestCase
{

    use PHPMock;
    
    /**
     * Tests opening the file fails.
     *
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testOpeningFails()
    {
        vfsStream::setup('test');
        @new FileStorage(vfsStream::url("test/nonexisting/test"));
    }

    /**
     * Tests seeking fails in setMicrotime().
     *
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testSetMicrotimeFailsSeeking()
    {
        $this->getFunctionMock(__NAMESPACE__, "fseek")
                ->expects($this->atLeastOnce())
                ->willReturn(-1);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url("test/data"));
        $storage->setMicrotime(1.1234);
    }

    /**
     * Tests writings fails in setMicrotime().
     *
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testSetMicrotimeFailsWriting()
    {
        $this->getFunctionMock(__NAMESPACE__, "fwrite")
                ->expects($this->atLeastOnce())
                ->willReturn(false);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url("test/data"));
        $storage->setMicrotime(1.1234);
    }

    /**
     * Tests seeking fails in getMicrotime().
     *
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testGetMicrotimeFailsSeeking()
    {
        $this->getFunctionMock(__NAMESPACE__, "fseek")
                ->expects($this->atLeastOnce())
                ->willReturn(-1);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url("test/data"));
        $storage->getMicrotime();
    }

    /**
     * Tests reading fails in getMicrotime().
     *
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testGetMicrotimeFailsReading()
    {
        $this->getFunctionMock(__NAMESPACE__, "fread")
                ->expects($this->atLeastOnce())
                ->willReturn(false);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url("test/data"));
        $storage->getMicrotime();
    }

    /**
     * Tests readinging too little in getMicrotime().
     *
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testGetMicrotimeReadsToLittle()
    {
        $data = new vfsStreamFile("data");
        $data->setContent("1234567");
        vfsStream::setup('test')->addChild($data);
        
        $storage = new FileStorage(vfsStream::url("test/data"));
        $storage->getMicrotime();
    }

    /**
     * Tests deleting fails.
     *
     * @test
     * @expectedException Securepoint\TokenBucket\Storage\StorageException
     */
    public function testRemoveFails()
    {
        $data = new vfsStreamFile("data");
        $root = vfsStream::setup('test');
        $root->chmod(0);
        $root->addChild($data);
        
        $storage = new FileStorage(vfsStream::url("test/data"));
        $storage->remove();
    }
}
