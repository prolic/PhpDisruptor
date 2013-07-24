<?php

namespace PhpDisruptor;

interface SequenceHolderInterface
{
    /**
     * @return Sequence[]
     */
    public function getSequences();

    /**
     * @param Sequence[] $sequences
     * @return bool true on success, false on failure (will always use cas)
     */
    public function casSequences(array $sequences);
}
