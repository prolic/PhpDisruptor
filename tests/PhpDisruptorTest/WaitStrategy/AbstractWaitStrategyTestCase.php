<?php

namespace PhpDisruptorTest\WaitStrategy;

use PhpDisruptor\Sequence;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use PhpDisruptorTest\WaitStrategy\TestAsset\DummySequenceBarrier;
use PhpDisruptorTest\WaitStrategy\TestAsset\SequenceUpdater;

abstract class AbstractWaitStrategyTestCase extends \PHPUnit_Framework_TestCase
{
    public function assertWaitForWithDelayOf($sleepTimeMicros, WaitStrategyInterface $waitStrategy)
    {
        $sequenceUpdater = new SequenceUpdater($sleepTimeMicros, $waitStrategy);
        $sequenceUpdater->start();
        $sequenceUpdater->waitForStartup();
        $cursor = new Sequence(0);
        $s = $sequenceUpdater->getSequence();
        $barrier = new DummySequenceBarrier();
        $sequence = $waitStrategy->waitFor(0, $cursor, $s, $barrier);

        $this->assertSame(0, $sequence);
    }
}
