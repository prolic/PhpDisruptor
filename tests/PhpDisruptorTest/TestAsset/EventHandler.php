<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use Stackable;

class EventHandler extends Stackable implements EventHandlerInterface
{
    protected $eventClass;

    public function __construct($eventClass)
    {
        $this->eventClass = $eventClass;
    }

    public function run()
    {
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
        echo get_class($event) . '-' . $sequence . '-' . (string) (int) $endOfBatch;
    }
}
