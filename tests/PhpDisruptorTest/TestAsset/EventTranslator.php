<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use ConcurrentPhpUtils\NoOpStackable;

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
        return 'ConcurrentPhpUtils\NoOpStackable';
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
        $string = '';
        if (null !== $args) {
            foreach ($args as $arg) {
                if ($arg instanceof NoOpStackable) {
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
