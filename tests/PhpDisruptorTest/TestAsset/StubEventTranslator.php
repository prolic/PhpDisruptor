<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use ConcurrentPhpUtils\NoOpStackable;
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
     * @param NoOpStackable $args
     * @return void
     */
    public function translateTo($event, $sequence, NoOpStackable $args = null)
    {
        if (null === $args) {
            $args = new NoOpStackable();
            $args[0] = 'error'; // error condition
            $args[1] = 'error';
        }
        $event->setValue($args[0]);
        $event->setTestString($args[1]);
    }

    public function run()
    {
    }
}
