<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

class TestEventProcessor2 extends AbstractEventProcessor
{
    public $sequenceBarrier;

    public $sequence;

    public $hash;

    public function __construct(SequenceBarrierInterface $sequenceBarrier)
    {
        $this->sequenceBarrier = $sequenceBarrier;
        $this->hash = uuid_create();
        $this->sequence = new Sequence();
    }

    /**
     * check if two stackables are the same
     *
     * @param UuidStackable $other
     * @return bool
     */
    public function equals(self $other)
    {
        $result = (int) uuid_compare($this->hash, $other->hash);
        return 0 == $result;
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
        try {
            $this->sequenceBarrier->waitFor(0);
        } catch (\Exception $e) {
            throw new \RuntimeException('', 0, $e);
        }

        $newSequence = $this->sequence->get() + 1;
        $this->sequence->set($newSequence);
    }
}