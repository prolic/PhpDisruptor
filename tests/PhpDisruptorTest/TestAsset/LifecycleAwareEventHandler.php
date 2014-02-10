<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\LifecycleAwareInterface;
use PhpDisruptor\Pthreads\CountDownLatch;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Pthreads\UuidStackable;

class LifecycleAwareEventHandler extends StackableArray implements EventHandlerInterface, LifecycleAwareInterface
{
    public $startCounter;

    public $shutdownCounter;

    public $startLatch;

    public $shutdownLatch;

    public function __construct(CountDownLatch $startLatch, CountDownLatch $shutdownLatch)
    {
        $this->startCounter = 0;
        $this->shutdownCounter = 0;

        $this->startLatch = $startLatch;
        $this->shutdownLatch = $shutdownLatch;
    }

    public function getEventClass()
    {
        return __NAMESPACE__ . '\StubEvent';
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
