<?php

namespace PhpDisruptor;

use PhpDisruptor\Pthreads\StackableArray;
use Stackable;

final class AggregateEventHandler extends Stackable implements EventHandlerInterface, LifecycleAwareInterface
{
    /**
     * @var string
     */
    public $eventClass;

    /**
     * @var EventHandlerInterface[]
     */
    public $eventHandlers;

    /**
     * Constructor
     *
     * @param string $eventClass
     * @param EventHandlerInterface[] $eventHandlers
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($eventClass, StackableArray $eventHandlers)
    {
        if (!class_exists($eventClass)) {
            throw new Exception\InvalidArgumentException(
                'event class "' . $eventClass . '" does not exist'
            );
        }
        $this->eventClass = $eventClass;

        foreach ($eventHandlers as $eventHandler) {
            if (!$eventHandler instanceof EventHandlerInterface
                || $eventHandler->getEventClass() != $eventClass
            ) {
                throw new Exception\InvalidArgumentException(
                    'all event handler must use the same event class as the aggregate event handler, '
                    . ' in this case: "' . $eventClass .'"'
                );
            }
        }
        $this->eventHandlers = $eventHandlers;
    }

    public function run()
    {
    }

    /**
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
        if (!is_object($event)) {
            throw new Exception\InvalidArgumentException('event must be an object');
        }
        if (!is_numeric($sequence)) {
            throw new Exception\InvalidArgumentException('$sequence must be an integer');
        }
        if (!is_bool($endOfBatch)) {
            throw new Exception\InvalidArgumentException('$endOfBatch must be a boolean');
        }
        $eventClass = $this->getEventClass();
        if (!$event instanceof $eventClass) {
            throw new Exception\InvalidArgumentException('$event must be an instance of ' . $eventClass);
        }

        foreach ($this->eventHandlers as $eventHandler) {
            $eventHandler->onEvent($event, $sequence, $endOfBatch);
        }
    }

    /**
     * Called once on thread start before first event is available.
     *
     * @return void
     */
    public function onStart()
    {
        foreach ($this->eventHandlers as $eventHandler) {
            if ($eventHandler instanceof LifecycleAwareInterface) {
                $eventHandler->onStart();
            }
        }
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
        foreach ($this->eventHandlers as $eventHandler) {
            if ($eventHandler instanceof LifecycleAwareInterface) {
                $eventHandler->onShutdown();
            }
        }
    }
}
