<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Sequence;

class StubEventProcessor extends AbstractEventProcessor
{
    protected $sequence;

    public function setSequence(Sequence $sequence)
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

    /**
     * @return void
     */
    public function halt()
    {
    }

    public function run()
    {
    }
}
