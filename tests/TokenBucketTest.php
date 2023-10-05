<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests;

use InvalidArgumentException;
use LengthException;
use malkusch\lock\mutex\NoMutex;
use phpmock\environment\MockEnvironment;
use phpmock\environment\SleepEnvironmentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Rate;
use Securepoint\TokenBucket\Storage\SingleProcessStorage;
use Securepoint\TokenBucket\Storage\Storage;
use Securepoint\TokenBucket\TokenBucket;

/**
 * Test for TokenBucket.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @see TokenBucket
 */
class TokenBucketTest extends TestCase
{
    /**
     * @var MockEnvironment Mock for microtime() and usleep().
     */
    private $sleepEnvironent;

    protected function setUp(): void
    {
        $builder = new SleepEnvironmentBuilder();
        $builder->addNamespace(__NAMESPACE__)
            ->addNamespace('Securepoint\\TokenBucket')
            ->addNamespace('Securepoint\\TokenBucket\\Util')
            ->setTimestamp(1417011228);

        $this->sleepEnvironent = $builder->build();
        $this->sleepEnvironent->enable();
    }

    protected function tearDown(): void
    {
        $this->sleepEnvironent->disable();
    }

    /**
     * Tests bootstrap() is bootstraps not on already bootstrapped storages.
     */
    public function testBootstrapOnce()
    {
        $storage = $this->getMockBuilder(Storage::class)
            ->getMock();
        $storage->expects($this->any())
            ->method('getMutex')
            ->willReturn(new NoMutex());
        $storage->expects($this->any())
            ->method('isBootstrapped')
            ->willReturn(true);

        $bucket = new TokenBucket(1, new Rate(1, Rate::SECOND), $storage);

        $storage->expects($this->never())
            ->method('bootstrap');

        $bucket->bootstrap();
    }

    /**
     * Tests bootstrapping sets to 0 tokens.
     */
    public function testDefaultBootstrap()
    {
        $rate = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap();

        $this->assertFalse($tokenBucket->consume(1));
    }

    /**
     * Tests bootstrapping with tokens.
     *
     * @param int $capacity The capacity.
     * @param int $tokens   The initial amount of tokens.
     */
    #[DataProvider('provideTestBootstrapWithInitialTokens')]
    public function testBootstrapWithInitialTokens($capacity, $tokens)
    {
        $rate = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket($capacity, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap($tokens);

        $this->assertTrue($tokenBucket->consume($tokens));
        $this->assertFalse($tokenBucket->consume(1));
    }

    /**
     * Returns test cases for testBootstrapWithInitialTokens().
     *
     * @return int[][] Test cases.
     */
    public static function provideTestBootstrapWithInitialTokens()
    {
        return [[10, 1], [10, 10]];
    }

    /**
     * Tests comsumption of cumulated tokens.
     */
    public function testConsume()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(10);

        $this->assertTrue($bucket->consume(1));
        $this->assertTrue($bucket->consume(2));
        $this->assertTrue($bucket->consume(3));
        $this->assertTrue($bucket->consume(4));

        $this->assertFalse($bucket->consume(1));

        sleep(3);
        $this->assertFalse($bucket->consume(4, $seconds));
        $this->assertEquals(1, $seconds);
    }

    /**
     * Tests consume() returns the expected amount of seconds to wait.
     */
    public function testWaitCalculation()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(1);

        $bucket->consume(3, $seconds);
        $this->assertEquals(2, $seconds);
        sleep(1);

        $bucket->consume(3, $seconds);
        $this->assertEquals(1, $seconds);
        sleep(1);

        $bucket->consume(3, $seconds);
        $this->assertEquals(0, $seconds);
    }

    /**
     * Test token rate.
     */
    public function testWaitingAddsTokens()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap();

        $this->assertFalse($bucket->consume(1));

        sleep(1);
        $this->assertTrue($bucket->consume(1));

        sleep(2);
        $this->assertTrue($bucket->consume(2));
    }

    /**
     * Tests consuming insuficient tokens wont remove any token.
     */
    public function testConsumeInsufficientDontRemoveTokens()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(1);

        $this->assertFalse($bucket->consume(2, $seconds));
        $this->assertEquals(1, $seconds);

        $this->assertFalse($bucket->consume(2, $seconds));
        $this->assertEquals(1, $seconds);

        $this->assertTrue($bucket->consume(1));
    }

    /**
     * Tests consuming tokens.
     */
    public function testConsumeSufficientRemoveTokens()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(1);

        $this->assertTrue($bucket->consume(1));
        $this->assertFalse($bucket->consume(1, $seconds));
        $this->assertEquals(1, $seconds);
    }

    /**
     * Tests bootstrapping with too many tokens.
     */
    public function testInitialTokensTooMany()
    {
        $this->expectException(LengthException::class);
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(20, $rate, new SingleProcessStorage());
        $bucket->bootstrap(21);
    }

    /**
     * Tests consuming more than the capacity.
     */
    public function testConsumeTooMany()
    {
        $this->expectException(LengthException::class);
        $rate = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket(20, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap();

        $tokenBucket->consume(21);
    }

    /**
     * Test the capacity limit of the bucket
     */
    public function testCapacity()
    {
        $rate = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap();
        sleep(11);

        $this->assertTrue($tokenBucket->consume(10));
        $this->assertFalse($tokenBucket->consume(1));
    }

    /**
     * Tests building a token bucket with an invalid caüacity fails.
     */
    #[DataProvider('provideTestInvalidCapacity')]
    public function testInvalidCapacity($capacity)
    {
        $this->expectException(InvalidArgumentException::class);
        $rate = new Rate(1, Rate::SECOND);
        new TokenBucket($capacity, $rate, new SingleProcessStorage());
    }

    /**
     * Provides tests cases for testInvalidCapacity().
     *
     * @return array Test cases.
     */
    public static function provideTestInvalidCapacity()
    {
        return [[0], [-1]];
    }

    /**
     * After bootstraping, getTokens() should return the initial amount.
     */
    public function testGetTokensShouldReturnInitialAmountOnBootstrap()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());

        $bucket->bootstrap(10);

        $this->assertEquals(10, $bucket->getTokens());
    }

    /**
     * After one consumtion, getTokens() should return the initial amount - 1.
     */
    public function testGetTokensShouldReturnRemainingTokensAfterConsumption()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(10);

        $bucket->consume(1);

        $this->assertEquals(9, $bucket->getTokens());
    }

    /**
     * After consuming all, getTokens() should return 0.
     */
    public function testGetTokensShouldReturnZeroTokensAfterConsumingAll()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(10);

        $bucket->consume(10);

        $this->assertEquals(0, $bucket->getTokens());
    }

    /**
     * After consuming too many, getTokens() should return the same as before.
     */
    public function testGetTokensShouldReturnSameAfterConsumingTooMany()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(10);

        try {
            $bucket->consume(11);
            $this->fail('Expected an exception.');
        } catch (LengthException) {
            // expected
        }

        $this->assertEquals(10, $bucket->getTokens());
    }

    /**
     * After waiting on an non full bucket, getTokens() should return more.
     */
    public function testGetTokensShouldReturnMoreAfterWaiting()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(5);

        sleep(1);

        $this->assertEquals(6, $bucket->getTokens());
    }

    /**
     * After waiting the complete refill period on an empty bucket, getTokens()
     * should return the capacity of the bucket.
     */
    public function testGetTokensShouldReturnCapacityAfterWaitingRefillPeriod()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);

        sleep(10);

        $this->assertEquals(10, $bucket->getTokens());
    }

    /**
     * After waiting longer than the complete refill period on an empty bucket,
     * getTokens() should return the capacity of the bucket.
     */
    public function testGetTokensShouldReturnCapacityAfterWaitingLongerThanRefillPeriod()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);

        sleep(11);

        $this->assertEquals(10, $bucket->getTokens());
    }
}
