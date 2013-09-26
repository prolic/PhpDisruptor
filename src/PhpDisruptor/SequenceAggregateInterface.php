<?php

namespace PhpDisruptor;

use PhpDisruptor\Pthreads\StackableArray;

interface SequenceAggregateInterface
{
    /**
     * @return Sequence[]
     */
    public function getSequences();

    /**
     * @param Sequence[] $sequences
     * @return void
     */
    public function setSequences(StackableArray $sequences);

    /**
     * @param Sequence[] $oldSequences
     * @param Sequence[] $newSequences
     * @return bool
     */
    public function casSequences(StackableArray $oldSequences, StackableArray $newSequences);
}
