<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\ExecutorInterface;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WorkerPool;

class WorkerPoolInfo implements ConsumerInfoInterface, EventClassCapableInterface
{
    /**
     * @var WorkerPool
     */
    protected $workerPool;

    /**
     * @var SequenceBarrierInterface
     */
    protected $sequenceBarrier;

    /**
     * @var bool
     */
    protected $endOfChain = true;

    /**
     * @var string
     */
    protected $eventClass;

    /**
     * Constructor
     *
     * @param WorkerPool $workerPool
     * @param SequenceBarrierInterface $sequenceBarrier
     */
    public function __construct(WorkerPool $workerPool, SequenceBarrierInterface $sequenceBarrier)
    {
        $this->workerPool = $workerPool;
        $this->eventClass = $workerPool->getEventClass();
        $this->sequenceBarrier = $sequenceBarrier;
    }

    /**
     * @return string
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * @return Sequence[]
     */
    public function getSequences()
    {
        return $this->workerPool->getWorkerSequences();
    }

    /**
     * @return SequenceBarrierInterface
     */
    public function getBarrier()
    {
        return $this->sequenceBarrier;
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
        $this->workerPool->start($executor);
    }

    /**
     * @return void
     */
    public function halt()
    {
        $this->workerPool->halt();
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
        return $this->workerPool->isRunning();
    }
}
