<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Redis;
use Securepoint\TokenBucket\Storage\PHPRedisStorage;
use Securepoint\TokenBucket\Storage\StorageException;

/**
 * Tests for PHPRedisStorage.
 *
 * These tests need the environment variable REDIS_URI.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see PHPRedisStorage
 */
class PHPRedisStorageTest extends TestCase
{
    /**
     * @var Redis The API.
     */
    private $redis;

    /**
     * @var PHPRedisStorage The SUT.
     */
    private $storage;

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('REDIS_URI')) {
            $this->markTestSkipped();
        }
        $uri = parse_url(getenv('REDIS_URI'));
        $this->redis = new Redis();
        $this->redis->connect($uri['host']);

        $this->storage = new PHPRedisStorage('test', $this->redis);
    }

    /**
     * Tests broken server communication.
     *
     * @param callable $method The tested method.
     */
    #[DataProvider('provideTestBrokenCommunication')]
    public function testBrokenCommunication(callable $method)
    {
        $this->expectException(StorageException::class);
        $this->redis->close();
        call_user_func($method, $this->storage);
    }

    /**
     * Provides test cases for testBrokenCommunication().
     *
     * @return array Testcases.
     */
    public static function provideTestBrokenCommunication()
    {
        return [
            [function (PHPRedisStorage $storage) {
                $storage->bootstrap(1);
            }],
            [function (PHPRedisStorage $storage) {
                $storage->isBootstrapped();
            }],
            [function (PHPRedisStorage $storage) {
                $storage->remove();
            }],
            [function (PHPRedisStorage $storage) {
                $storage->setMicrotime(1);
            }],
            [function (PHPRedisStorage $storage) {
                $storage->getMicrotime();
            }],
        ];
    }

    /**
     * Tests remove() fails.
     */
    public function testRemoveFails()
    {
        $this->expectException(StorageException::class);
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->remove();
    }

    /**
     * Tests setMicrotime() fails.
     */
    public function testSetMicrotimeFails()
    {
        $this->expectException(StorageException::class);
        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('set')
            ->willReturn(false);
        $storage = new PHPRedisStorage('test', $redis);
        $storage->setMicrotime(1);
    }

    /**
     * Tests getMicrotime() fails.
     */
    public function testGetMicrotimeFails()
    {
        $this->expectException(StorageException::class);
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->getMicrotime();
    }
}
