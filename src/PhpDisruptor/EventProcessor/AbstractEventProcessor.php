<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\Sequence;
use Stackable;

abstract class AbstractEventProcessor extends Stackable
{
    /**
     * Get a reference to the Sequence being used by this EventProcessor.
     *
     * @return Sequence reference to the Sequence for this EventProcessor
     */
    abstract public function getSequence();

    /**
     * @return void
     */
    abstract public function halt();
}
