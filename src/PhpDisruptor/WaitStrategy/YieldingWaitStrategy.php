<?php

namespace PhpDisruptor\WaitStrategy;

use PhpDisruptor\Exception;
use Threaded;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

/**
 * Yielding strategy that uses a Thread::wait() for EventProcessors waiting on a barrier
 * after an initially spinning.
 *
 * This strategy is a good compromise between performance and CPU resource without incurring significant latency spikes.
 */
final class YieldingWaitStrategy extends Threaded implements WaitStrategyInterface
{
    /**
     * @var int
     */
    const SPIN_TRIES = 100;

    /**
     * Wait for the given sequence to be available.  It is possible for this method to return a value
     * less than the sequence number supplied depending on the implementation of the WaitStrategy.  A common
     * use for this is to signal a timeout.  Any EventProcessor that is using a WaitStragegy to get notifications
     * about message becoming available should remember to handle this case.  The BatchEventProcessor explicitly
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
    ) {
        $counter = self::SPIN_TRIES;

        while (($availableSequence = $dependentSequence->get()) < $sequence) {

            $barrier->checkAlert();

            if (0 == $counter) {
                $this->wait(1);
            } else {
                --$counter;
            }
        }

        return $availableSequence;
    }

    /**
     * Implementations should signal the waiting EventProcessors that the cursor has advanced.
     *
     * @return void
     */
    public function signalAllWhenBlocking()
    {
    }
}
