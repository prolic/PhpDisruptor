<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use ConcurrentPhpUtils\NoOpStackable;
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
        return 'ConcurrentPhpUtils\NoOpStackable';
    }

    public function run()
    {
    }

    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param NoOpStackable|null $args
     * @return void
     */
    public function translateTo($event, $sequence, NoOpStackable $args = null)
    {
        $event[0] = (string) $args[0] . $args[1] . $args[2] . $args[3] . '-' . $sequence;
    }

}
