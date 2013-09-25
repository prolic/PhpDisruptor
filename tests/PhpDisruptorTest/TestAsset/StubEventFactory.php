<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventFactoryInterface;

final class StubEventFactory implements EventFactoryInterface
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
        return new StubEvent(-1);
    }
}
