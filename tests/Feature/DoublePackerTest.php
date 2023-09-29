<?php

namespace Securepoint\TokenBucket\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\StorageException;
use Securepoint\TokenBucket\Util\DoublePacker;

/**
 * Tests for DoublePacker.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see  DoublePacker
 */
class DoublePackerTest extends TestCase
{

    /**
     * Tests pack().
     *
     * @param string $expected The expected string.
     * @param double $input The input double.
     *
     * @test
     */
    #[DataProvider('provideTestPack')]
    public function testPack($expected, $input)
    {
        $this->assertEquals($expected, DoublePacker::pack($input));
    }

    /**
     * Provides test cases for testPack().
     *
     * @return array Test cases.
     */
    public function provideTestPack()
    {
        return [
            [pack("d", 0), 0],
            [pack("d", 0.1), 0.1],
            [pack("d", 1), 1],
        ];
    }

    /**
     * Tests unpack() fails.
     *
     * @param string $input The input string.
     *
     * @test
     */
    #[DataProvider('provideTestUnpackFails')]
    public function testUnpackFails($input)
    {
        $this->expectException(StorageException::class);
        DoublePacker::unpack($input);
    }

    /**
     * Provides test cases for testUnpackFails().
     *
     * @return array Test cases.
     */
    public function provideTestUnpackFails()
    {
        return [
            [""],
            ["1234567"],
            ["123456789"],
        ];
    }

    /**
     * Tests unpack().
     *
     * @param double $expected The expected double.
     * @param string $input The input string.
     *
     * @test
     */
    #[DataProvider('provideTestUnpack')]
    public function testUnpack($expected, $input)
    {
        $this->assertEquals($expected, DoublePacker::unpack($input));
    }

    /**
     * Provides test cases for testConvert().
     *
     * @return array Test cases.
     */
    public function provideTestUnpack()
    {
        return [
            [0, pack("d", 0)],
            [0.1, pack("d", 0.1)],
            [1, pack("d", 1)],
            [1.1, pack("d", 1.1)],
        ];
    }
}