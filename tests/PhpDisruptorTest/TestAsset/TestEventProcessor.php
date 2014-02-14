<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Sequence;

class TestEventProcessor extends AbstractEventProcessor
{
    public $sequence;

    public $hash;

    public function __construct(Sequence $sequence)
    {
        $this->sequence = $sequence;
        $this->hash = uuid_create();
    }

    /**
     * check if two stackables are the same
     *
     * @param UuidNoOpStackable $other
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
    }
}