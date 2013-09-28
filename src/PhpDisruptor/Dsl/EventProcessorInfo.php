<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use Stackable;

class EventProcessorInfo extends Stackable implements ConsumerInfoInterface
{
    /**
     * @var AbstractEventProcessor
     */
    public $eventProcessor;

    /**
     * @var EventHandlerInterface
     */
    public $handler;

    /**
     * @var SequenceBarrierInterface
     */
    public $barrier;

    /**
     * @var bool
     */
    public $endOfChain;

    /**
     * Constructor
     *
     * @param AbstractEventProcessor $eventProcessor
     * @param EventHandlerInterface $handler
     * @param SequenceBarrierInterface $barrier
     */
    public function __construct(
        AbstractEventProcessor $eventProcessor,
        EventHandlerInterface $handler,
        SequenceBarrierInterface $barrier
    ) {
        $this->eventProcessor = $eventProcessor;
        $this->handler = $handler;
        $this->barrier = $barrier;
        $this->endOfChain = true;
    }

    /**
     * @return AbstractEventProcessor
     */
    public function getEventProcessor()
    {
        return $this->eventProcessor;
    }

    /**
     * @return Sequence[]
     */
    public function getSequences()
    {
        $sequences = new StackableArray();
        $sequences[] = $this->eventProcessor->getSequence();
        return $sequences;
    }

    /**
     * @return EventHandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return SequenceBarrierInterface
     */
    public function getBarrier()
    {
        return $this->barrier;
    }

    /**
     * @return bool
     */
    public function isEndOfChain()
    {
        return $this->endOfChain;
    }

    public function run()
    {
        $this->eventProcessor->start();
    }

    /**
     * @return void
     */
    public function shutdown()
    {
        $this->eventProcessor->shutdown();
    }

    /**
     * @return void
     */
    public function markAsUsedInBarrier()
    {
        $this->endOfChain = false;
    }
}
