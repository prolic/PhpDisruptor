<?php

namespace PhpDisruptor;

use Threaded;

interface EventTranslatorInterface extends EventClassCapableInterface
{
    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param Threaded|null $args
     * @return void
     */
    public function translateTo($event, $sequence, Threaded $args = null);
}
