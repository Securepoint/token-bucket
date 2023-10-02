<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\FileStorage;
use Securepoint\TokenBucket\Storage\StorageException;

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
     */
    public function testOpeningFails()
    {
        $this->expectException(StorageException::class);
        vfsStream::setup('test');
        @new FileStorage(vfsStream::url('test/nonexisting/test'));
    }

    /**
     * Tests seeking fails in setMicrotime().
     */
    public function testSetMicrotimeFailsSeeking()
    {
        $this->expectException(StorageException::class);
        $this->getFunctionMock('Securepoint\\TokenBucket\\Storage', 'fseek')
            ->expects($this->atLeastOnce())
            ->willReturn(-1);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url('test/data'));
        $storage->setMicrotime(1.1234);
    }

    /**
     * Tests writings fails in setMicrotime().
     */
    public function testSetMicrotimeFailsWriting()
    {
        $this->expectException(StorageException::class);
        $this->getFunctionMock('Securepoint\\TokenBucket\\Storage', 'fwrite')
            ->expects($this->atLeastOnce())
            ->willReturn(false);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url('test/data'));
        $storage->setMicrotime(1.1234);
    }

    /**
     * Tests seeking fails in getMicrotime().
     */
    public function testGetMicrotimeFailsSeeking()
    {
        $this->expectException(StorageException::class);
        $this->getFunctionMock('Securepoint\\TokenBucket\\Storage', 'fseek')
            ->expects($this->atLeastOnce())
            ->willReturn(-1);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url('test/data'));
        $storage->getMicrotime();
    }

    /**
     * Tests reading fails in getMicrotime().
     */
    public function testGetMicrotimeFailsReading()
    {
        $this->expectException(StorageException::class);
        $this->getFunctionMock('Securepoint\\TokenBucket\\Storage', 'fread')
            ->expects($this->atLeastOnce())
            ->willReturn(false);

        vfsStream::setup('test');
        $storage = new FileStorage(vfsStream::url('test/data'));
        $storage->getMicrotime();
    }

    /**
     * Tests readinging too little in getMicrotime().
     */
    public function testGetMicrotimeReadsToLittle()
    {
        $this->expectException(StorageException::class);
        $data = new vfsStreamFile('data');
        $data->setContent('1234567');
        vfsStream::setup('test')->addChild($data);

        $storage = new FileStorage(vfsStream::url('test/data'));
        $storage->getMicrotime();
    }

    /**
     * Tests deleting fails.
     */
    public function testRemoveFails()
    {
        $this->expectException(StorageException::class);
        $data = new vfsStreamFile('data');
        $root = vfsStream::setup('test');
        $root->chmod(0);
        $root->addChild($data);

        $storage = new FileStorage(vfsStream::url('test/data'));
        $storage->remove();
    }
}
