<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\SequencerInterface;

interface ConsumerInfoInterface
{
    /**
     * Get sequences
     *
     * @return SequencerInterface[]
     */
    public function getSequences();

    /**
     * Get barrier
     *
     * @return SequenceBarrierInterface
     */
    public function getBarrier();

    /**
     * Check if is end of chain
     *
     * @return bool
     */
    public function isEndOfChain();

    /**
     * Mark as used in barrier
     *
     * @return void
     */
    public function markAsUsedInBarrier();
}
