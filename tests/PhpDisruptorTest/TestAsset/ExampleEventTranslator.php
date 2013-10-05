<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventTranslatorInterface;
use Stackable;

class ExampleEventTranslator extends Stackable implements EventTranslatorInterface
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return 'PhpDisruptorTest\TestAsset\StubEvent';
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
        $event->setTestString($this->value);
    }

    public function run()
    {
    }
}
