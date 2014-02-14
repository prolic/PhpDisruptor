<?php

namespace PhpDisruptorTest\TestAsset;

use ConcurrentPhpUtils\NoOpStackable;
use PhpDisruptor\RingBuffer;

class StubEventProcessorThread extends \Thread
{
    public $eventProcessors;

    public function __construct(NoOpStackable $eventProcessors)
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
