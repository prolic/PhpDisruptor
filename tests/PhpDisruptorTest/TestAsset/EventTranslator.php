<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use Threaded;

class EventTranslator extends Threaded implements EventTranslatorInterface
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
        $string = '';
        if (null !== $args) {
            foreach ($args as $arg) {
                if ($arg instanceof Threaded) {
                    foreach ($arg as $v) {
                        $string .= $v;
                    }
                } else {
                    $string .= $arg;
                }
            }
        }
        $event[0]  = $string . '-' . $sequence;
    }
}
