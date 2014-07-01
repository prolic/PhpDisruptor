<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventFactoryInterface;
use Threaded;

final class TestEventFactory extends Threaded implements EventFactoryInterface
{
    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return __NAMESPACE__ . '\TestEvent';
    }

    public function newInstance()
    {
        return new TestEvent();
    }
}
