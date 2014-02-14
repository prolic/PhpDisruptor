<?php

namespace PhpDisruptor;

use ConcurrentPhpUtils\NoOpStackable;

interface EventTranslatorInterface extends EventClassCapableInterface
{
    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param NoOpStackable|null $args
     * @return void
     */
    public function translateTo($event, $sequence, NoOpStackable $args = null);
}
