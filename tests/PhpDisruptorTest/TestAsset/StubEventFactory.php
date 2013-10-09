<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventFactoryInterface;
use Stackable;

final class StubEventFactory extends Stackable implements EventFactoryInterface
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

    public function run()
    {
    }
}
