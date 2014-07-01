<?php

namespace PhpDisruptorTest\Dsl\Disruptor\TestAsset;

use ConcurrentPhpUtils\CasThreadedMemberTrait;
use ConcurrentPhpUtils\CyclicBarrier;
use ConcurrentPhpUtils\Exception\BrokenBarrierException;
use Threaded;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\LifecycleAwareInterface;

class DelayedEventHandler extends Threaded implements EventHandlerInterface, LifecycleAwareInterface
{
    use CasThreadedMemberTrait;

    public $readyToProcessEvent;

    public $stopped;

    /**
     * @var CyclicBarrier
     */
    public $cyclicBarrier;

    /**
     * Constructor
     *
     * @param CyclicBarrier|null $barrier
     */
    public function __construct(CyclicBarrier $barrier = null)
    {
        $this->readyToProcessEvent = false;
        $this->stopped = false;
        if (null === $barrier) {
            $barrier = new CyclicBarrier(2);
        }
        $this->cyclicBarrier = $barrier;
    }

    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return 'PhpDisruptorTest\TestAsset\TestEvent';
    }

    /**
     * Called when a publisher has published an event to the RingBuffer
     *
     * @param object $event published to the RingBuffer
     * @param int $sequence of the event being processed
     * @param bool $endOfBatch flag to indicate if this is the last event in a batch from the RingBuffer
     * @return void
     * @throws \Exception if the EventHandler would like the exception handled further up the chain.
     */
    public function onEvent($event, $sequence, $endOfBatch)
    {
        $this->waitForAndSetFlag(false);
    }

    public function processEvent()
    {
        $this->waitForAndSetFlag(true);
    }

    public function stopWaiting()
    {
        $this->stopped = true;
    }

    public function waitForAndSetFlag($bool)
    {
        while(!$this->casMember('readyToProcessEvent', !$bool, $bool)) {
            $this->wait(1);
        }
    }

    public function onStart()
    {
        try
        {
            $this->cyclicBarrier->await();
        } catch (BrokenBarrierException $e) {
            throw new \RuntimeException($e);
        }
    }

    public function onShutdown()
    {
    }

    public function awaitStart()
    {
        $this->cyclicBarrier->await();
    }
}
