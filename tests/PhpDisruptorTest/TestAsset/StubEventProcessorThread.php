<?php

namespace PhpDisruptorTest\TestAsset;

use Threaded;

class StubEventProcessorThread extends \Thread
{
    public $eventProcessors;

    public function __construct(Threaded $eventProcessors)
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
