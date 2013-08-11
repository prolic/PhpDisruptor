<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\ExecutorInterface;
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
     * @param ExecutorInterface $executor
     * @return void
     */
    public function start(ExecutorInterface $executor);

    /**
     * @return void
     */
    public function halt();

    /**
     * @return void
     */
    public function markAsUsedInBarrier();

    /**
     * @return bool
     */
    public function isRunning();
}
