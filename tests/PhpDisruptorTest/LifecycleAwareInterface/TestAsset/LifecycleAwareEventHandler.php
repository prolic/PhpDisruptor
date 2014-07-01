<?php

namespace PhpDisruptorTest\LifecycleAwareInterface\TestAsset;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\LifecycleAwareInterface;
use ConcurrentPhpUtils\CountDownLatch;
use Threaded;

class LifecycleAwareEventHandler extends Threaded implements EventHandlerInterface, LifecycleAwareInterface
{
    public $startCounter;

    public $shutdownCounter;

    public $startLatch;

    public $shutdownLatch;

    /**
     * Constructor
     *
     * @param CountDownLatch $startLatch
     * @param CountDownLatch $shutdownLatch
     */
    public function __construct(CountDownLatch $startLatch, CountDownLatch $shutdownLatch)
    {
        $this->startCounter = 0;
        $this->shutdownCounter = 0;

        $this->startLatch = $startLatch;
        $this->shutdownLatch = $shutdownLatch;
    }

    public function getEventClass()
    {
        return 'PhpDisruptorTest\TestAsset\StubEvent';
    }

    public function onEvent($event, $sequence, $endOfBatch)
    {
    }

    public function onStart()
    {
        ++$this->startCounter;
        $this->startLatch->countDown();
    }

    public function onShutdown()
    {
        ++$this->shutdownCounter;
        $this->shutdownLatch->countDown();
    }
}
