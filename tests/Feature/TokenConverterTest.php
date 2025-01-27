<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Rate;
use Securepoint\TokenBucket\Util\TokenConverter;

/**
 * Tests for TokenConverter.
 *
 * @license WTFPL
 * @see  TokenConverter
 */
class TokenConverterTest extends TestCase
{
    use PHPMock;

    /**
     * Tests convertSecondsToTokens().
     *
     * @param int $expected The expected tokens.
     * @param double $seconds The seconds.
     * @param Rate $rate The rate.
     */
    #[DataProvider('provideTestConvertSecondsToTokens')]
    public function testConvertSecondsToTokens($expected, $seconds, Rate $rate)
    {
        $converter = new TokenConverter($rate);
        $this->assertEquals($expected, $converter->convertSecondsToTokens($seconds));
    }

    /**
     * Provides test cases for testConvertSecondsToTokens().
     *
     * @return array Test cases.
     */
    public static function provideTestConvertSecondsToTokens()
    {
        return [
            [0, 0.9, new Rate(1, Rate::SECOND)],
            [1, 1, new Rate(1, Rate::SECOND)],
            [1, 1.1, new Rate(1, Rate::SECOND)],

            [1000, 1, new Rate(1, Rate::MILLISECOND)],
            [2000, 2, new Rate(1, Rate::MILLISECOND)],

            [0, 59, new Rate(1, Rate::MINUTE)],
            [1, 60, new Rate(1, Rate::MINUTE)],
            [1, 61, new Rate(1, Rate::MINUTE)],
        ];
    }

    /**
     * Tests convertTokensToSeconds().
     *
     * @param double $expected The expected seconds.
     * @param int $tokens The tokens.
     * @param Rate $rate The rate.
     */
    #[DataProvider('provideTestconvertTokensToSeconds')]
    public function testconvertTokensToSeconds($expected, $tokens, Rate $rate)
    {
        $converter = new TokenConverter($rate);
        $this->assertEquals($expected, $converter->convertTokensToSeconds($tokens));
    }

    /**
     * Provides test cases for testconvertTokensToSeconds().
     *
     * @return array Test cases.
     */
    public static function provideTestconvertTokensToSeconds()
    {
        return [
            [0.001, 1, new Rate(1, Rate::MILLISECOND)],
            [0.002, 2, new Rate(1, Rate::MILLISECOND)],
            [1, 1, new Rate(1, Rate::SECOND)],
            [2, 2, new Rate(1, Rate::SECOND)],
        ];
    }

    /**
     * Tests convertTokensToMicrotime().
     *
     * @param double $delta The expected delta.
     * @param int $tokens The tokens.
     * @param Rate $rate The rate.
     */
    #[DataProvider('provideTestConvertTokensToMicrotime')]
    public function testConvertTokensToMicrotime($delta, $tokens, Rate $rate)
    {
        $microtime = $this->getFunctionMock('Securepoint\\TokenBucket\\Util', 'microtime');
        $microtime->expects($this->any())
            ->willReturn(100000);

        $microtime = $this->getFunctionMock('Securepoint\\TokenBucket\\Tests\\Feature', 'microtime');
        $microtime->expects($this->any())
            ->willReturn(100000);

        $converter = new TokenConverter($rate);

        $this->assertEquals(microtime(true) + $delta, $converter->convertTokensToMicrotime($tokens));
    }

    /**
     * Provides test cases for testConvertTokensToMicrotime().
     *
     * @return array Test cases.
     */
    public static function provideTestConvertTokensToMicrotime()
    {
        return [
            [-1, 1, new Rate(1, Rate::SECOND)],
            [-2, 2, new Rate(1, Rate::SECOND)],
            [-0.001, 1, new Rate(1, Rate::MILLISECOND)],
        ];
    }
}
