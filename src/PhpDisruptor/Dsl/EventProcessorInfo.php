<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\EventProcessor\EventProcessorInterface;
use PhpDisruptor\ExecutorInterface;
use PhpDisruptor\SequenceBarrierInterface;

class EventProcessorInfo implements ConsumerInfoInterface
{
    /**
     * @var EventProcessorInterface
     */
    protected $eventProcessor;

    /**
     * @var EventHandlerInterface
     */
    protected $handler;

    /**
     * @var SequenceBarrierInterface
     */
    protected $barrier;

    /**
     * @var bool
     */
    protected $endOfChain = true;

    /**
     * Constructor
     *
     * @param EventProcessorInterface $eventProcessor
     * @param EventHandlerInterface $handler
     * @param SequenceBarrierInterface $barrier
     */
    public function __construct(
        EventProcessorInterface $eventProcessor,
        EventHandlerInterface $handler,
        SequenceBarrierInterface $barrier
    ) {
        $this->eventProcessor = $eventProcessor;
        $this->handler = $handler;
        $this->barrier = $barrier;
    }

    /**
     * @return EventProcessorInterface
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
        return array($this->eventProcessor->getSequence());
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

    /**
     * @param ExecutorInterface $executor
     * @return void
     */
    public function start(ExecutorInterface $executor)
    {
        $executor->execute($this->eventProcessor);
    }

    /**
     * @return void
     */
    public function halt()
    {
        $this->eventProcessor->halt();
    }

    /**
     * @return void
     */
    public function markAsUsedInBarrier()
    {
        $this->endOfChain = false;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->eventProcessor->isRunning();
    }
}
