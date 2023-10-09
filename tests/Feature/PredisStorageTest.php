<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\ClientException;
use Predis\Response\Error;
use Securepoint\TokenBucket\Storage\PredisStorage;
use Securepoint\TokenBucket\Storage\StorageException;

/**
 * Tests for PredisStorage.
 *
 * These tests need the environment variable REDIS_URI.
 *
 * @license WTFPL
 * @see PredisStorage
 */
class PredisStorageTest extends TestCase
{
    /**
     * @var Client The API.
     */
    private Client $redis;

    /**
     * @var PredisStorage The SUT.
     */
    private PredisStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('REDIS_URI')) {
            $this->markTestSkipped();
        }
        $this->redis = new Client(getenv('REDIS_URI'));
        $this->storage = new PredisStorage('test', $this->redis);
    }

    /**
     * Tests broken server communication.
     *
     * @param callable $method The tested method.
     */
    #[DataProvider('provideTestBrokenCommunication')]
    public function testBrokenCommunication(callable $method): void
    {
        $this->expectException(StorageException::class);
        $redis = $this->createMock(Client::class);
        $redis->expects($this->once())
            ->method('__call')
            ->willThrowException(new ClientException());
        $storage = new PredisStorage('test', $redis);
        call_user_func($method, $storage);
    }

    /**
     * Provides test cases for testBrokenCommunication().
     *
     * @return array<int,array<callable>> Testcases.
     */
    public static function provideTestBrokenCommunication(): array
    {
        return [
            [function (PredisStorage $storage) {
                $storage->bootstrap(1);
            }],
            [function (PredisStorage $storage) {
                $storage->isBootstrapped();
            }],
            [function (PredisStorage $storage) {
                $storage->remove();
            }],
            [function (PredisStorage $storage) {
                $storage->setMicrotime(1);
            }],
            [function (PredisStorage $storage) {
                $storage->getMicrotime();
            }],
        ];
    }

    /**
     * Tests remove() fails.
     */
    public function testRemoveFails(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->remove();
    }

    /**
     * Tests setMicrotime() fails.
     */
    public function testSetMicrotimeFails(): void
    {
        $this->expectException(StorageException::class);
        $redis = $this->createMock(Client::class);
        $redis->expects($this->once())
            ->method('__call')
            ->with('set')
            ->willReturn(new Error('error'));
        $storage = new PredisStorage('test', $redis);
        $storage->setMicrotime(1);
    }

    /**
     * Tests getMicrotime() fails.
     */
    public function testGetMicrotimeFails(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->getMicrotime();
    }
}
