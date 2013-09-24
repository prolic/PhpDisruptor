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
     * @return SequenceBarrierInterface
     */
    public function getBarrier();

    /**
     * @return bool
     */
    public function isEndOfChain();

    /**
     * @return void
     */
    public function markAsUsedInBarrier();
}
