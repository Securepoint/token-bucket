<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Rate;

/**
 * Test for Rate.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @see Rate
 */
class RateTest extends TestCase
{
    /**
     * Tests getTokensPerSecond().
     *
     * @param double $expected The expected rate in tokens per second.
     * @param Rate   $rate     The rate.
     */
    #[DataProvider(methodName: 'provideTestGetTokensPerSecond')]
    public function testGetTokensPerSecond(float $expected, Rate $rate)
    {
        $this->assertEquals($expected, $rate->getTokensPerSecond());
    }

    /**
     * Provides tests cases for testGetTokensPerSecond().
     *
     * @return array Test cases.
     */
    public static function provideTestGetTokensPerSecond(): array
    {
        return [
            [1 / 31556926, new Rate(1, Rate::YEAR)],
            [2 / 31556926, new Rate(2, Rate::YEAR)],
            [1 / 2629743.83, new Rate(1, Rate::MONTH)],
            [2 / 2629743.83, new Rate(2, Rate::MONTH)],
            [1 / 604800, new Rate(1, Rate::WEEK)],
            [2 / 604800, new Rate(2, Rate::WEEK)],
            [1 / 60 / 60 / 24, new Rate(1, Rate::DAY)],
            [2 / 60 / 60 / 24, new Rate(2, Rate::DAY)],
            [1 / 60 / 60, new Rate(1, Rate::HOUR)],
            [2 / 60 / 60, new Rate(2, Rate::HOUR)],
            [1 / 60, new Rate(1, Rate::MINUTE)],
            [2 / 60, new Rate(2, Rate::MINUTE)],
            [1, new Rate(1, Rate::SECOND)],
            [2, new Rate(2, Rate::SECOND)],
            [1000, new Rate(1, Rate::MILLISECOND)],
            [2000, new Rate(2, Rate::MILLISECOND)],
            [1000000, new Rate(1, Rate::MICROSECOND)],
            [2000000, new Rate(2, Rate::MICROSECOND)],
        ];
    }

    /**
     * Tests building a rate with an invalid unit fails.
     */
    public function testInvalidUnit()
    {
        $this->expectException(InvalidArgumentException::class);
        new Rate(1, 'invalid');
    }

    /**
     * Tests building a rate with an invalid amount fails.
     */
    #[DataProvider(methodName: 'provideTestInvalidAmount')]
    public function testInvalidAmount($amount)
    {
        $this->expectException(InvalidArgumentException::class);
        new Rate($amount, Rate::SECOND);
    }

    /**
     * Provides tests cases for testInvalidAmount().
     *
     * @return array Test cases.
     */
    public static function provideTestInvalidAmount()
    {
        return [[0], [-1]];
    }
}
