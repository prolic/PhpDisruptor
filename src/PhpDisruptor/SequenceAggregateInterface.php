<?php

namespace PhpDisruptor;

use PhpDisruptor\Lists\SequenceList;

interface SequenceAggregateInterface
{
    /**
     * @return SequenceList
     */
    public function getSequences();

    /**
     * @param SequenceList $sequences
     * @return void
     */
    public function setSequences(SequenceList $sequences);

    /**
     * @param SequenceList $oldSequences
     * @param SequenceList $newSequences
     * @return bool
     */
    public function casSequences(SequenceList $oldSequences, SequenceList $newSequences);
}
