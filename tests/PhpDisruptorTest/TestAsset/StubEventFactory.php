<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventFactoryInterface;
use Threaded;

final class StubEventFactory extends Threaded implements EventFactoryInterface
{
    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return __NAMESPACE__ . '\StubEvent';
    }

    public function newInstance()
    {
        $event = new StubEvent(-1);
        return $event;
    }
}
