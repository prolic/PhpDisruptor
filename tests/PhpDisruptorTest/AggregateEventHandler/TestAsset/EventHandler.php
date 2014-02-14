<?php

namespace PhpDisruptorTest\AggregateEventHandler\TestAsset;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\LifecycleAwareInterface;
use ConcurrentPhpUtils\NoOpStackable;

class EventHandler extends NoOpStackable implements EventHandlerInterface, LifecycleAwareInterface
{
    /**
     * @var string
     */
    public $eventClass;

    /**
     * @var ResultCounter
     */
    public $result;


    /**
     * Constructor
     *
     * @param string $eventClass
     * @param ResultCounter $result
     */
    public function __construct($eventClass, ResultCounter $result)
    {
        $this->eventClass = $eventClass;
        $this->result = $result;
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
     * @throws \Exception if the EventHandler would like the exception handled further up the chain.
     */
    public function onEvent($event, $sequence, $endOfBatch)
    {
        $this->result->appendToResult();
    }

    /**
     * Called once on thread start before first event is available.
     *
     * @return void
     */
    public function onStart()
    {
        $this->result->appendToResult();
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
        $this->result->appendToResult();
    }


    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
}
