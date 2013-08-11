<?php

namespace PhpDisruptor\WaitStrategy;

use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

class BusySpinWaitStrategy implements WaitStrategyInterface
{
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
