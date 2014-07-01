<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Sequence;

class TestEventProcessor extends AbstractEventProcessor
{
    public $sequence;

    public function __construct(Sequence $sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * Get a reference to the Sequence being used by this EventProcessor.
     *
     * @return Sequence reference to the Sequence for this EventProcessor
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    public function halt()
    {
    }
}