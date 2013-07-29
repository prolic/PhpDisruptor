<?php

namespace PhpDisruptor;

interface EventTranslatorInterface extends EventClassCapableInterface
{
    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param array $args
     * @return void
     */
    public function translateTo($event, $sequence, array $args = array());
}
