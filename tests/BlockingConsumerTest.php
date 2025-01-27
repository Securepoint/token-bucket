<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests;

use phpmock\environment\MockEnvironment;
use phpmock\environment\SleepEnvironmentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\BlockingConsumer;
use Securepoint\TokenBucket\Rate;
use Securepoint\TokenBucket\Storage\SingleProcessStorage;
use Securepoint\TokenBucket\TimeoutException;
use Securepoint\TokenBucket\TokenBucket;

/**
 * Test for BlockingConsumer.
 *
 * @license WTFPL
 * @see BlockingConsumer
 */
class BlockingConsumerTest extends TestCase
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
     * Tests comsumption of cumulated tokens.
     */
    public function testConsecutiveConsume()
    {
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $consumer = new BlockingConsumer($bucket);
        $bucket->bootstrap(10);
        $time = microtime(true);

        $consumer->consume(1);
        $consumer->consume(2);
        $consumer->consume(3);
        $consumer->consume(4);
        $this->assertEquals(microtime(true) - $time, 0);

        $consumer->consume(1);
        $this->assertEquals(microtime(true) - $time, 1);

        sleep(3);
        $time = microtime(true);
        $consumer->consume(4);
        $this->assertEquals(microtime(true) - $time, 1);
    }

    /**
     * Tests consume().
     *
     * @param double $expected The expected duration.
     * @param int    $tokens   The tokens to consume.
     * @param Rate   $rate     The rate.
     */
    #[DataProvider('provideTestConsume')]
    public function testConsume($expected, $tokens, Rate $rate)
    {
        $bucket = new TokenBucket(10000, $rate, new SingleProcessStorage());
        $consumer = new BlockingConsumer($bucket);
        $bucket->bootstrap();

        $time = microtime(true);
        $consumer->consume($tokens);
        $this->assertEquals($expected, number_format(microtime(true) - $time, 1));
    }

    /**
     * Returns test cases for testConsume().
     *
     * @return array Test cases.
     */
    public static function provideTestConsume()
    {
        return [
            [0.5, 500, new Rate(1, Rate::MILLISECOND)],
            [1, 1000, new Rate(1, Rate::MILLISECOND)],
            [1.5, 1500, new Rate(1, Rate::MILLISECOND)],
            [2, 2000, new Rate(1, Rate::MILLISECOND)],
            [2.5, 2500, new Rate(1, Rate::MILLISECOND)],
        ];
    }

    /**
     * Tests consume() won't sleep less than one millisecond.
     */
    public function testMinimumSleep()
    {
        $rate = new Rate(10, Rate::MILLISECOND);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap();

        $consumer = new BlockingConsumer($bucket);
        $time = microtime(true);

        $consumer->consume(1);
        $this->assertLessThan(1e-5, abs((microtime(true) - $time) - 0.001));
    }

    /**
     * consume() should fail after a timeout.
     */
    public function testConsumeShouldFailAfterTimeout()
    {
        $this->expectException(TimeoutException::class);
        $rate = new Rate(0.1, Rate::SECOND);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);
        $consumer = new BlockingConsumer($bucket, 9);

        $consumer->consume(1);
    }

    /**
     * consume() should not fail before a timeout.
     */
    public function testConsumeShouldNotFailBeforeTimeout()
    {
        $this->expectNotToPerformAssertions();
        $rate = new Rate(0.1, Rate::SECOND);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);
        $consumer = new BlockingConsumer($bucket, 11);

        $consumer->consume(1);
    }

    /**
     * consume() should not never time out.
     */
    public function testConsumeWithoutTimeoutShouldNeverFail()
    {
        $this->expectNotToPerformAssertions();
        $rate = new Rate(0.1, Rate::YEAR);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);
        $consumer = new BlockingConsumer($bucket);

        $consumer->consume(1);
    }
}
