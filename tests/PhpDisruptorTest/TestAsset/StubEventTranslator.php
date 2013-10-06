<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Pthreads\StackableArray;
use Stackable;

class StubEventTranslator extends Stackable implements EventTranslatorInterface
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

    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param StackableArray $args
     * @return void
     */
    public function translateTo($event, $sequence, StackableArray $args)
    {
        $event->setValue($args[0]);
        $event->setTestString($args[1]);
    }

    public function run()
    {
    }
}
