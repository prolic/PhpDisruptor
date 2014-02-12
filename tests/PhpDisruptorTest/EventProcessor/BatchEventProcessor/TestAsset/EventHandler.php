<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\LifecycleAwareInterface;
use PhpDisruptor\Pthreads\CountDownLatch;
use PhpDisruptor\Pthreads\StackableArray;

class EventHandler extends StackableArray implements EventHandlerInterface, LifecycleAwareInterface
{
    /**
     * @var string
     */
    public $eventClass;

    /**
     * @var array
     */
    public $result;

    /**
     * @var CountDownLatch
     */
    public $latch;

    /**
     * Constructor
     *
     * @param string $eventClass
     * @param CountDownLatch $latch
     */
    public function __construct($eventClass, CountDownLatch $latch)
    {
        $this->eventClass = $eventClass;
        $this->result = new StackableArray();
        $this->latch = $latch;
    }

    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * Called when a publisher has published an event to the RingBuffer
     *
     * @param object $event published to the RingBuffer
     * @param int $sequence of the event being processed
     * @param bool $endOfBatch flag to indicate if this is the last event in a batch from the RingBuffer
     * @return void
     * @throws Exception\ExceptionInterface if the EventHandler would like the exception handled further up the chain.
     */
    public function onEvent($event, $sequence, $endOfBatch)
    {
        $this->result[] = get_class($event) . '-' . $sequence . '-' . (string) (int) $endOfBatch;
        $this->latch->countDown();
    }

    /**
     * Called once on thread start before first event is available.
     *
     * @return void
     */
    public function onStart()
    {
        $this->result[] = 'onStart';
    }

    /**
     * Called once just before the thread is shutdown.
     *
     * Sequence event processing will already have stopped before this method is called. No events will
     * be processed after this message.
     *
     * @return void
     */
    public function onShutdown()
    {
        $this->result[] = 'onShutdown';
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
}
