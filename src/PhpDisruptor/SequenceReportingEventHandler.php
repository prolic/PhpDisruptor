<?php

namespace PhpDisruptor;

interface SequenceReportingEventHandlerInterface extends EventHandlerInterface
{
    /**
     * Call by the {@link BatchEventProcessor} to setup the callback.
     *
     * @param Sequence $sequenceCallback callback on which to notify
     * the {@link BatchEventProcessor} that the sequence has progressed.
     * @return void
     */
    public function setSequenceCallback(Sequence $sequenceCallback);
}
