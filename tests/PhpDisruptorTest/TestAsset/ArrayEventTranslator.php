<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use Threaded;

class ArrayEventTranslator extends Threaded implements EventTranslatorInterface
{
    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return 'Threaded';
    }

    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param Threaded|null $args
     * @return void
     */
    public function translateTo($event, $sequence, Threaded $args = null)
    {
        $event[0] = (string) $args[0] . $args[1] . $args[2] . $args[3] . '-' . $sequence;
    }

}
