<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\Sequence;
use Worker;

abstract class AbstractEventProcessor extends Worker
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
