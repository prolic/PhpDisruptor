<?php

namespace PhpDisruptorTest\WaitStrategy\TestAsset;

use PhpDisruptor\Exception;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\SequenceBarrierInterface;

class DummySequenceBarrier extends StackableArray implements SequenceBarrierInterface
{
    /**
     * Wait for the given sequence to be available for consumption.
     *
     * @param int $sequence to wait for
     * @return int the sequence up to which is available
     * @throws Exception\AlertException if a status change has occurred for the Disruptor
     * @throws Exception\InterruptedException if the thread needs awaking on a condition variable.
     * @throws Exception\TimeoutException
     */
    public function waitFor($sequence)
    {
        return 0;
    }

    /**
     * Get the current cursor value that can be read.
     *
     * @return int value of the cursor for entries that have been published.
     */
    public function getCursor()
    {
        return 0;
    }

    /**
     * The current alert status for the barrier.
     *
     * @return bool true if in alert otherwise false.
     */
    public function isAlerted()
    {
        return false;
    }

    /**
     * Alert the EventProcessors of a status change and stay in this status until cleared.
     *
     * @return void
     */
    public function alert()
    {
    }

    /**
     * Clear the current alert status.
     *
     * @return void
     */
    public function clearAlert()
    {
    }

    /**
     * Check if an alert has been raised and throw an AlertException if it has.
     *
     * @return void
     * @throws Exception\AlertException if alert has been raised.
     */
    public function checkAlert()
    {
    }
}
