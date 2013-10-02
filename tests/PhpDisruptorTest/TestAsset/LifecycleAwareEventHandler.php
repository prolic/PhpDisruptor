<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\LifecycleAwareInterface;
use PhpDisruptor\Pthreads\UuidStackable;

class LifecycleAwareEventHandler extends UuidStackable implements EventHandlerInterface, LifecycleAwareInterface
{
    /**
     * @inheritdoc
     */
    public function getEventClass()
    {
        return 'stdClass';
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
