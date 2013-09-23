<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\LifecycleAwareInterface;


class LifecycleAwareEventHandler implements EventHandlerInterface, LifecycleAwareInterface
{
    /**
     * @var string
     */
    protected $eventClass;

    /**
     * Constructor
     *
     * @param string $eventClass
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($eventClass)
    {
        if (!class_exists($eventClass)) {
            throw new Exception\InvalidArgumentException(
                'Event class ' . $eventClass . ' not found'
            );
        }
        $this->eventClass = $eventClass;
    }

    /**
     * @inheritdoc
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * @inheritdoc
     */
    public function onEvent($event, $sequence, $endOfBatch)
    {
    }

    /**
     * @inheritdoc
     */
    public function onStart()
    {
    }

    /**
     * @inheritdoc
     */
    public function onShutdown()
    {
    }
}
