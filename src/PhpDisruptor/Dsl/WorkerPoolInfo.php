<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WorkerPool;
use Threaded;

class WorkerPoolInfo extends Threaded implements ConsumerInfoInterface, EventClassCapableInterface
{
    /**
     * @var WorkerPool
     */
    public $workerPool;

    /**
     * @var SequenceBarrierInterface
     */
    public $sequenceBarrier;

    /**
     * @var bool
     */
    public $endOfChain;

    /**
     * @var string
     */
    public $eventClass;

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
        $this->endOfChain = true;
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

    public function run()
    {
        $this->workerPool->start();
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
