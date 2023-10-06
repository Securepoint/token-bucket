<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\StorageException;
use Securepoint\TokenBucket\Util\DoublePacker;

/**
 * Tests for DoublePacker.
 *
 * @license WTFPL
 * @see  DoublePacker
 */
class DoublePackerTest extends TestCase
{
    /**
     * Tests pack().
     *
     * @param string $expected The expected string.
     * @param int|float $input The input double.
     */
    #[DataProvider('provideTestPack')]
    public function testPack(string $expected, int|float $input): void
    {
        $this->assertEquals($expected, DoublePacker::pack($input));
    }

    /**
     * Provides test cases for testPack().
     *
     * @return array<int,array<int,string|int|float>> Test cases.
     */
    public static function provideTestPack(): array
    {
        return [[pack('d', 0), 0], [pack('d', 0.1), 0.1], [pack('d', 1), 1]];
    }

    /**
     * Tests unpack() fails.
     *
     * @param string $input The input string.
     */
    #[DataProvider('provideTestUnpackFails')]
    public function testUnpackFails(string $input): void
    {
        $this->expectException(StorageException::class);
        DoublePacker::unpack($input);
    }

    /**
     * Provides test cases for testUnpackFails().
     *
     * @return array<int,array<int,string>> Test cases.
     */
    public static function provideTestUnpackFails(): array
    {
        return [[''], ['1234567'], ['123456789']];
    }

    /**
     * Tests unpack().
     *
     * @param float $expected The expected double.
     * @param string $input The input string.
     * @throws StorageException
     */
    #[DataProvider('provideTestUnpack')]
    public function testUnpack(float $expected, string $input): void
    {
        $this->assertEquals($expected, DoublePacker::unpack($input));
    }

    /**
     * Provides test cases for testConvert().
     *
     * @return array<int,array<int,string|int|float>> Test cases.
     */
    public static function provideTestUnpack(): array
    {
        return [[0, pack('d', 0)], [0.1, pack('d', 0.1)], [1, pack('d', 1)], [1.1, pack('d', 1.1)]];
    }
}
