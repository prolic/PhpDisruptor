<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\Pthreads\AbstractAtomicWorker;
use PhpDisruptor\Sequence;

abstract class AbstractEventProcessor extends AbstractAtomicWorker
{
    /**
     * Get a reference to the Sequence being used by this EventProcessor.
     *
     * @return Sequence reference to the Sequence for this EventProcessor
     */
    abstract public function getSequence();

    /**
     * Signal that this EventProcessor should stop when it has finished consuming at the next clean break.
     * It will call {@link SequenceBarrierInterface#alert()} to notify the thread to check status.
     *
     * @return void
     */
    abstract public function halt();

    /**
     * @return bool
     */
    abstract public function isRunning();
}
