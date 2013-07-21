<?php

namespace PhpDisruptor;

interface WaitStrategyInterface
{
    /**
     * Wait for the given sequence to be available.  It is possible for this method to return a value
     * less than the sequence number supplied depending on the implementation of the WaitStrategy.  A common
     * use for this is to signal a timeout.  Any EventProcessor that is using a WaitStragegy to get notifications
     * about message becoming available should remember to handle this case.  The {@link BatchEventProcessor} explicitly
     * handles this case and will signal a timeout if required.
     *
     * @param int $sequence to be waited on.
     * @param Sequence $cursor the main sequence from ringbuffer. Wait/notify strategies will
     *    need this as it's the only sequence that is also notified upon update.
     * @param Sequence $dependentSequence on which to wait.
     * @param SequenceBarrierInterface $barrier the processor is waiting on.
     * @return int the sequence that is available which may be greater than the requested sequence.
     * @throws Exception\AlertException if the status of the Disruptor has changed.
     * @throws Exception\InterruptedException if the thread is interrupted.
     * @throws Exception\TimeoutException
     */
    public function waitFor(
        $sequence,
        Sequence $cursor,
        Sequence $dependentSequence,
        SequenceBarrierInterface $barrier
    );

    /**
     * Implementations should signal the waiting {@link EventProcessor}s that the cursor has advanced.
     *
     * @return void
     */
    public function signalAllWhenBlocking();
}
