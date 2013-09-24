<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

class EventProcessorInfo implements ConsumerInfoInterface
{
    /**
     * @var AbstractEventProcessor
     */
    private $eventProcessor;

    /**
     * @var EventHandlerInterface
     */
    private $handler;

    /**
     * @var SequenceBarrierInterface
     */
    private $barrier;

    /**
     * @var bool
     */
    private $endOfChain = true;

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
     * @return void
     */
    public function start()
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

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->eventProcessor->isRunning();
    }
}
