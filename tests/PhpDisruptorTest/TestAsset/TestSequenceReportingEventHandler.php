<?php

namespace PhpDisruptorTest\TestAsset;

use ConcurrentPhpUtils\CountDownLatch;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceReportingEventHandlerInterface;

class TestSequenceReportingEventHandler implements SequenceReportingEventHandlerInterface
{
    /**
     * @var CountDownLatch
     */
    public $latch;

    /**
     * @var Sequence
     */
    public $sequenceCallback;

    public function __construct(CountDownLatch $latch)
    {
        $this->latch = $latch;
    }

    public function getEventClass()
    {
        return __NAMESPACE__ . '\StubEvent';
    }

    public function setSequenceCallback(Sequence $sequenceTrackerCallback)
    {
        $this->sequenceCallback = $sequenceTrackerCallback;
    }

    public function onEvent($event, $sequence, $endOfBatch)
    {
        $this->sequenceCallback->set($sequence);
        $this->latch->countDown();
    }
}
