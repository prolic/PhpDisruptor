<?php

namespace PhpDisruptor\WaitStrategy;

use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use Stackable;

/**
 * Busy Spin strategy that uses a busy spin loop for EventProcessors waiting on a barrier.
 *
 * This strategy will use CPU resource to avoid syscalls which can introduce latency jitter.  It is best
 * used when threads can be bound to specific CPU cores.
 */
final class BusySpinWaitStrategy extends Stackable implements WaitStrategyInterface
{
    public function run()
    {
    }

    /**
     * @param int $sequence
     * @param Sequence $cursor
     * @param Sequence $dependentSequence
     * @param SequenceBarrierInterface $barrier
     * @return int
     * @throws Exception\AlertException
     * @throws Exception\InterruptedException
     */
    public function waitFor(
        $sequence,
        Sequence $cursor,
        Sequence $dependentSequence,
        SequenceBarrierInterface $barrier
    ) {
        while (($availableSequence = $dependentSequence->get()) < $sequence) {
            $barrier->checkAlert();
        }
        return $availableSequence;
    }

    /**
     * @inheritdoc
     */
    public function signalAllWhenBlocking()
    {
    }
}
