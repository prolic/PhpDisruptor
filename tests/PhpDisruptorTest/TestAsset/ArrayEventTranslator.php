<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Pthreads\StackableArray;
use Stackable;

class ArrayEventTranslator extends Stackable implements EventTranslatorInterface
{
    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return 'PhpDisruptor\Pthreads\StackableArray';
    }

    public function run()
    {
    }

    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param StackableArray|null $args
     * @return void
     */
    public function translateTo($event, $sequence, StackableArray $args = null)
    {
        $event[0] = (string) $args[0] . $args[1] . $args[2] . $args[3] . '-' . $sequence;
    }

}
