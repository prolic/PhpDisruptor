<?php

namespace PhpDisruptor\Dsl;

use ArrayIterator;
use IteratorAggregate;
use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\EventProcessor\EventProcessorInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WorkerPool;
use SplObjectStorage;

/**
* Provides a repository mechanism to associate EventHandlers with EventProcessors
*/
class ConsumerRepository implements EventClassCapableInterface, IteratorAggregate
{
    /**
     * @var SplObjectStorage
     */
    protected $eventProcessorInfoByEventHandler;

    /**
     * @var SplObjectStorage
     */
    protected $eventProcessorInfoBySequence;

    /**
     * @var ConsumerInfoInterface[]
     */
    protected $consumerInfos;

    /**
     * @var string
     */
    protected $eventClass;

    /**
     * Constructor
     *
     * @param EventFactoryInterface $eventFactory
     */
    public function __construct(EventFactoryInterface $eventFactory)
    {
        $this->eventClass = $eventFactory->getEventClass();
        $this->eventProcessorInfoByEventHandler = new SplObjectStorage();
        $this->eventProcessorInfoBySequence = new SplObjectStorage();
        $this->consumerInfos = array();
    }

    /**
     * @inheritdoc
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->consumerInfos);
    }

    /**
     * Add event processor
     *
     * @param EventProcessorInterface $eventProcessor
     * @param EventHandlerInterface $handler
     * @param SequenceBarrierInterface $barrier
     * @throws Exception\InvalidArgumentException
     */
    public function addEventProcessor(
        EventProcessorInterface $eventProcessor,
        EventHandlerInterface $handler = null,
        SequenceBarrierInterface $barrier = null
    ) {
        if ((null === $handler && null !== $barrier)
            || (null !== $handler && null === $barrier)
        ) {
            throw new Exception\InvalidArgumentException(
                'Even $handler and $barrier are null or both of them not'
            );
        }

        $consumerInfo = new EventProcessorInfo($eventProcessor, $handler, $barrier);
        $this->eventProcessorInfoBySequence->offsetSet($eventProcessor->getSequence(), $consumerInfo);
        $this->consumerInfos[] = $consumerInfo;

        if (null !== $handler) {
            if ($handler->getEventClass() != $this->getEventClass()) {
                throw new Exception\InvalidArgumentException(
                    'Given event handler does not match current event class, current is "'
                    . $this->getEventClass() . '", given "' . $handler->getEventClass() . '"'
                );
            }
            $this->eventProcessorInfoByEventHandler->offsetSet($handler, $consumerInfo);
        }
    }

    /**
     * Add worker pool
     *
     * @param WorkerPool $workerPool
     * @param SequenceBarrierInterface $sequenceBarrier
     */
    public function addWorkerPool(WorkerPool $workerPool, SequenceBarrierInterface $sequenceBarrier)
    {
        $workerPoolInfo = new WorkerPoolInfo($workerPool, $sequenceBarrier);
        $this->consumerInfos[] = $workerPoolInfo;
        foreach ($workerPool->getWorkerSequences() as $sequence) {
            $this->eventProcessorInfoBySequence->offsetSet($sequence, $workerPoolInfo);
        }
    }

    /**
     * Get last sequence in chain
     *
     * @param bool $includeStopped
     * @return Sequence[]
     */
    public function getLastSequenceInChain($includeStopped)
    {
        $includeStopped = (bool) $includeStopped;
        $lastSequences = array();
        foreach ($this->consumerInfos as $consumerInfo) {
            if (($includeStopped || $consumerInfo->isRunning()) && $consumerInfo->isEndOfChain()) {
                $sequences = $consumerInfo->getSequences();
                foreach ($sequences as $sequence) {
                    $lastSequences[] = $sequence;
                }
            }
        }
        return $lastSequences;
    }

    /**
     * Get event processor for event handler
     *
     * @param EventHandlerInterface $handler
     * @return EventProcessorInterface
     * @throws Exception\InvalidArgumentException
     */
    public function getEventProcessorFor(EventHandlerInterface $handler)
    {
        $eventProcessorInfo = $this->getEventProcessorInfo($handler);
        if (null === $eventProcessorInfo) {
            throw new Exception\InvalidArgumentException(
                'The given event handler is not processing events'
            );
        }
        return $eventProcessorInfo->getEventProcessor();
    }

    /**
     * Get sequence for event handler
     *
     * @param EventHandlerInterface $handler
     * @return Sequence
     */
    public function getSequenceFor(EventHandlerInterface $handler)
    {
        return $this->getEventProcessorFor($handler)->getSequence();
    }

    /**
     * Un-mark event processors as end of chain
     *
     * @param Sequence[] $barrierEventProcessors
     * @throws Exception\InvalidArgumentException
     */
    public function unMarkEventProcessorsAsEndOfChain(array $barrierEventProcessors)
    {
        foreach ($barrierEventProcessors as $barrierEventProcessor) {
            if (!$barrierEventProcessor instanceof Sequence) {
                throw new Exception\InvalidArgumentException(
                    '$barrierEventProcessors must be an array of Sequence'
                );
            }
            $this->getEventProcessorInfo($barrierEventProcessor)->markAsUsedInBarrier();
        }
    }

    /**
     * Get barrier for event handler
     *
     * @param EventHandlerInterface $handler
     * @return SequenceBarrierInterface|null
     */
    public function getBarrierFor(EventHandlerInterface $handler)
    {
        $consumerInfo = $this->getEventProcessorInfo($handler);
        if (null === $consumerInfo) {
            return null;
        }
        return $consumerInfo->getBarrier();
    }

    /**
     * Get event processor info by event handler
     *
     * @param EventHandlerInterface $handler
     * @return EventProcessorInfo
     */
    protected function getEventProcessorInfo(EventHandlerInterface $handler)
    {
        return $this->eventProcessorInfoByEventHandler->offsetGet($handler);
    }

    /**
     * Get event processor info by sequence
     *
     * @param Sequence $barrierEventProcessor
     * @return EventProcessorInfo
     */
    protected function getEventProcessorInfoBySequence(Sequence $barrierEventProcessor)
    {
        return $this->eventProcessorInfoBySequence->offsetGet($barrierEventProcessor);
    }
}
