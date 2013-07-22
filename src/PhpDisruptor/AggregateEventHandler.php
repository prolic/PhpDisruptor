<?php

namespace PhpDisruptor;

class AggregateEventHandler implements EventHandlerInterface, LifecycleAwareInterface
{
    /**
     * @var string
     */
    protected $eventClass;

    /**
     * @var EventHandlerInterface[]
     */
    protected $eventHandlers;

    /**
     * Constructor
     *
     * @param string $eventClass
     * @param EventHandlerInterface[] $eventHandlers
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($eventClass, array $eventHandlers)
    {
        if (!class_exists($eventClass)) {
            throw new Exception\InvalidArgumentException(
                'event class "' . $eventClass . '" does not exist'
            );
        }
        $event = new $eventClass;
        if (!$event instanceof EventInterface) {
            throw new Exception\InvalidArgumentException(
                'invalid event class given, must be an implementation of PhpDisruptor\EventInterface'
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

    /**
     * @return string
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * Called when a publisher has published an event to the {@link RingBuffer}
     *
     * @param EventInterface $event published to the {@link RingBuffer}
     * @param int $sequence of the event being processed
     * @param bool $endOfBatch flag to indicate if this is the last event in a batch from the {@link RingBuffer}
     * @return void
     * @throws Exception\ExceptionInterface if the EventHandler would like the exception handled further up the chain.
     */
    public function onEvent(EventInterface $event, $sequence, $endOfBatch)
    {
        if (!is_numeric($sequence)) {
            throw new Exception\InvalidArgumentException('$sequence must be an integer');
        }
        if (!is_bool($endOfBatch)) {
            throw new Exception\InvalidArgumentException('$endOfBatch must be a boolean');
        }
        $eventClass = $this->getEventClass();
        if ($event instanceof $eventClass) {
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
     * <p>Called once just before the thread is shutdown.</p>
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
