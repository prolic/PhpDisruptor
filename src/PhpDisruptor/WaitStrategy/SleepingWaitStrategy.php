<?php

namespace PhpDisruptor\WaitStrategy;

use ConcurrentPhpUtils\NoOpStackable;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

/**
 * Sleeping strategy that initially spins, then uses a Thread::wait(), and eventually for the minimum
 * number of nanos the OS and PHP allows while the EventProcessors are waiting on a barrier.
 *
 * This strategy is a good compromise between performance and CPU resource. Latency spikes can occur after quiet periods.
 */
final class SleepingWaitStrategy extends NoOpStackable implements WaitStrategyInterface
{
    /**
     * @var int
     */
    const RETRIES = 200;

    public function waitFor(
        $sequence,
        Sequence $cursor,
        Sequence $dependentSequence,
        SequenceBarrierInterface $barrier
    ) {
        $counter = self::RETRIES;

        while (($availableSequence = $dependentSequence->get()) < $sequence) {

            $barrier->checkAlert();

            if ($counter > 100) {
                --$counter;
            } else if ($counter > 0) {
                --$counter;
                $this->wait(1);
            } else {
                time_nanosleep(0, 1);
            }
        }

        return $availableSequence;
    }

    public function signalAllWhenBlocking()
    {
    }
}
