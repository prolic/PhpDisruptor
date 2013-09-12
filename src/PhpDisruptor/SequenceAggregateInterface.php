<?php

namespace PhpDisruptor;

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
    public function setSequences(array $sequences);

    /**
     * @param Sequence[] $oldSequences
     * @param Sequence[] $newSequences
     * @return bool
     */
    public function casSequences(array $oldSequences, array $newSequences);
}
