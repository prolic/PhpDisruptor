<?php

namespace PhpDisruptor;

use PhpDisruptor\Util\Util;
use Zend\Cache\Storage\StorageInterface;

class WorkerPool implements EventClassCapableInterface
{
    //private final AtomicBoolean started = new AtomicBoolean(false); todo: make atomic !!!

    /**
     * @var bool
     */
    protected $started;

    /**
     * @var Sequence
     */
    protected $workSequence;

    /**
     * @var RingBuffer
     */
    protected $ringBuffer;

    /**
     * @var WorkProcessor[]
     */
    protected $workProcessors;

    /**
     * @var string
     */
    protected $eventClass;

    /**
     * Protected Constructor, use createFromRingBuffer or createFromEventFactory instead
     *
     * @param RingBuffer $ringBuffer
     * @param SequenceBarrierInterface $sequenceBarrier
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param WorkHandlerInterface[] $workHandlers
     */
    protected function __construct(
        RingBuffer $ringBuffer,
        SequenceBarrierInterface $sequenceBarrier,
        ExceptionHandlerInterface $exceptionHandler,
        array $workHandlers
    ) {
        $this->workSequence = new Sequence($ringBuffer->getStorage(), SequencerInterface::INITIAL_CURSOR_VALUE);
        $this->ringBuffer = $ringBuffer;
        $this->eventClass = $ringBuffer->getEventClass();
        $this->workProcessors = array();
        foreach ($workHandlers as $workHandler) {
            $this->validateWorkHandler($workHandler);
            $this->workProcessors[] = new WorkProcessor($ringBuffer, $sequenceBarrier, $workHandler, $exceptionHandler, $this->workSequence);
        }
    }

    /**
     * @return string
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * Create worker pool from ring buffer
     *
     * @param RingBuffer $ringBuffer
     * @param SequenceBarrierInterface $sequenceBarrier
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param WorkHandlerInterface[] $workHandlers
     * @return static
     */
    public static function createFromRingBuffer(
        RingBuffer $ringBuffer,
        SequenceBarrierInterface $sequenceBarrier,
        ExceptionHandlerInterface $exceptionHandler,
        array $workHandlers
    ) {
        return new static($ringBuffer, $sequenceBarrier, $exceptionHandler, $workHandlers);
    }

    /**
     * Constructor
     *
     * @param StorageInterface $storage
     * @param EventFactoryInterface $eventFactory
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param array $workHandlers
     * @return static
     */
    public static function createFromEventFactory(
        StorageInterface $storage,
        EventFactoryInterface $eventFactory,
        ExceptionHandlerInterface $exceptionHandler,
        array $workHandlers
    ) {
        $ringBuffer = RingBuffer::createMultiProducer($storage, $eventFactory, 1024);
        $sequenceBarrier = $ringBuffer->newBarrier();

        $workerPool = new static($ringBuffer, $sequenceBarrier, $exceptionHandler, $workHandlers);

        $ringBuffer->addGatingSequences($workerPool->getWorkerSequences());

        return $workerPool;
    }

    /**
     * @return Sequence[]
     */
    public function getWorkerSequences()
    {
        $sequences = array();
        foreach ($this->workProcessors as $workProcessor) {
            $sequences[] = $workProcessor->getSequence();
        }
        return $sequences;
    }

    /**
     * Start the worker pool processing events in sequence
     *
     * @param ExecutorInterface $executor
     * @return RingBuffer
     * @throws Exception\InvalidArgumentException
     */
    public function start(ExecutorInterface $executor)
    {
        if ($this->started) { // Todo: make atomic: if (!started.compareAndSet(false, true))
            throw new Exception\InvalidArgumentException(
                'WorkerPool has already been started and cannot be restarted until halted'
            );
        }

        $cursor = $this->ringBuffer->getCursor();
        $this->workSequence->set($cursor);

        foreach ($this->workProcessors as $workProcessor) {
            $workProcessor->getSequence()->set($cursor);
            $executor->execute($workProcessor);
        }

        return $this->ringBuffer;
    }

    /**
     * Wait for the RingBuffer to drain of published events then halt the workers
     *
     * @return void
     */
    public function drainAndHalt()
    {
        $workerSequences = $this->getWorkerSequences();
        while ($this->ringBuffer->getCursor() > Util::getMinimumSequence($workerSequences)) {
            // Thread.yield(); // @todo implement
        }

        $this->halt();
    }

    /**
     * Halt all workers immediately at the end of their current cycle
     *
     * @return void
     */
    public function halt()
    {
        foreach ($this->workProcessors as $workProcessor) {
            $workProcessor->halt();
        }
        $this->started = false;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->started;
    }

    /**
     * @param WorkHandlerInterface $workHandler
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    protected function validateWorkHandler(WorkHandlerInterface $workHandler)
    {
        if ($workHandler->getEventClass() != $this->ringBuffer->getEventClass()) {
            throw new Exception\InvalidArgumentException(
                'All work handlers must use the event class as the ring buffer, buffer has "'
                . $this->ringBuffer->getEventClass() . '" and current handler has "'
                . $workHandler->getEventClass() . '"'
            );
        }
    }
}