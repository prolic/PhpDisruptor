<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;

class StubEventProcessorThread extends \Thread
{
    public $eventProcessors;

    public function __construct(StackableArray $eventProcessors)
    {
        $this->eventProcessors = $eventProcessors;
    }

    public function run()
    {
        foreach ($this->eventProcessors as $stubWorker) {
            $stubWorker->setSequence($stubWorker->getSequence()->get() + 1);
        }
    }
}
