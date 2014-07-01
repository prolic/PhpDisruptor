<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Sequence;

class StubEventProcessor extends AbstractEventProcessor
{
    /**
     * @var Sequence
     */
    protected $sequence;

    public function __construct()
    {
        $this->sequence = new Sequence();
    }

    public function setSequence($sequence)
    {
        $this->sequence->set($sequence);
    }

    public function setSequenceObject(Sequence $se)
    {
        $this->sequence = $se;
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
}
