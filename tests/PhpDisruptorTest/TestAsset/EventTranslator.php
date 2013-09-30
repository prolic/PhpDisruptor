<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;

class EventTranslator extends \Stackable implements EventTranslatorInterface
{
    public function run()
    {
    }

    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return 'stdClass';
    }

    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param array $args
     * @return void
     */
    public function translateTo($event, $sequence, array $args = array())
    {
        $event->result  = join('', $args) . '-' . $sequence;
    }
}
