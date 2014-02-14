<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use PhpDisruptor\Lists\EventHandlerList;
use PhpDisruptor\Lists\EventProcessorList;
use PhpDisruptor\Lists\SequenceList;
use PhpDisruptor\Lists\WorkHandlerList;
use ConcurrentPhpUtils\NoOpStackable;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use PhpDisruptor\WorkerPool;
use PhpDisruptor\WorkHandlerInterface;
use PhpDisruptor\Util\Util;
use Stackable;
use Worker;

/**
 * A DSL-style API for setting up the disruptor pattern around a ring buffer (aka the Builder pattern).
 */
class Disruptor extends Worker implements EventClassCapableInterface
{
    /**
     * @var RingBuffer
     */
    public $ringBuffer;

    /**
     * @var Worker
     */
    public $worker;

    /**
     * @var ConsumerRepository
     */
    public $consumerRepository;

    /**
     * @var bool
     */
    public $started;

    /**
     * @var ExceptionHandlerInterface
     */
    public $exceptionHandler;

    /**
     * Constructor
     *
     * @param RingBuffer $ringBuffer
     * @param $worker
     */
    protected function __construct(RingBuffer $ringBuffer, Worker $worker)
    {
        $this->ringBuffer = $ringBuffer;
        $this->worker = $worker;
        $this->started = false;
    }

    public function run()
    {
        $gaitingSequences = $this->consumerRepository->getLastSequenceInChain(true);
        $this->ringBuffer->addGatingSequences($gaitingSequences);

        $this->_checkOnlyStartedOnce();

        foreach ($this->consumerRepository as $consumerInfo) {
            /* @var ConsumerInfoInterface $consumerInfo*/
            $consumerInfo->start($this->worker);
        }
    }

    /**
     * Create disruptor instance
     *
     * @param EventFactoryInterface $eventFactory
     * @param $ringBufferSize
     * @param Worker $worker
     * @param ProducerType $producerType
     * @param WaitStrategyInterface|null $waitStrategy
     * @return Disruptor
     */
    public static function create(
        EventFactoryInterface $eventFactory,
        $ringBufferSize,
        Worker $worker,
        ProducerType $producerType,
        WaitStrategyInterface $waitStrategy = null
    ) {
        $ringBuffer = RingBuffer::create($producerType, $eventFactory, $ringBufferSize, $waitStrategy);
        return static::createFromRingBuffer($ringBuffer, $worker);
    }

    /**
     * @param RingBuffer $ringBuffer
     * @param Worker $worker
     * @return Disruptor
     */
    public static function createFromRingBuffer(RingBuffer $ringBuffer, Worker $worker)
    {
        return new static($ringBuffer, $worker);
    }

    /**
     * @inheritdoc
     */
    public function getEventClass()
    {
        return $this->ringBuffer->getEventClass();
    }


    /**
     * Set up event handlers to handle events from the ring buffer. These handlers will process events
     * as soon as they become available, in parallel
     *
     * This method can be used as the start of a chain. For example if the handler "A" must
     * process events before handler "B":
     *
     * $dw->handleEventsWithEventHandlers($A)->then($B);
     *
     * @param EventHandlerList $handlers
     * @return EventHandlerGroup that can be used to chain dependencies
     */
    public function handleEventsWithEventHandlers(EventHandlerList $handlers)
    {
        return $this->createEventProcessors(null, $handlers);
    }

    /**
     * Set up custom event processors to handle events from the ring buffer. The Disruptor will
     * automatically start this processors when #start() is called
     *
     * @param EventProcessorList $processors
     * @return EventHandlerGroup that can be used to chain dependencies
     */
    public function handleEventsWithEventProcessors(EventProcessorList $processors)
    {
        foreach ($processors as $processor) {
            $this->consumerRepository->addEventProcessor($processor);
        }
        return new EventHandlerGroup($this, $this->consumerRepository, Util::getSequencesFor($processors));
    }

    /**
     * Set up a WorkerPool to distribute an event to one of a pool of work handler threads.
     * Each event will only be processed by one of the work handlers.
     * The Disruptor will automatically start this processors when #start() is called.
     *
     * @param WorkHandlerList $workHandlers the work handlers that will process events.
     * @return EventHandlerGroup that can be used to chain dependencies.
     */
    public function handleEventsWithWorkerPool(WorkHandlerList $workHandlers)
    {
        return $this->createWorkerPool(null, $workHandlers);
    }

    /**
     * Specify an exception handler to be used for any future event handlers
     *
     * Note that only event handlers set up after calling this method will use the exception handler
     *
     * @param ExceptionHandlerInterface $exceptionHandler the exception handler to use for any future EventProcessor
     */
    public function handleExceptionsWith(ExceptionHandlerInterface $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Override the default exception handler for a specific handler.
     *
     * $disruptorWizard->handleExceptionsIn($eventHandler)->with($exceptionHandler);
     *
     * @param EventHandlerInterface $eventHandler the event handler to set a different exception handler for
     * @return ExceptionHandlerSetting dsl object - intended to be used by chaining the with method call
     */
    public function handleExceptionsFor(EventHandlerInterface $eventHandler)
    {
        return new ExceptionHandlerSetting($eventHandler, $this->consumerRepository);
    }

    /**
     * Create a group of event handlers to be used as a dependency.
     * For example if the handler "A" must process events before handler "B":
     *
     * $dw->afterEventHandlers($A)->handleEventsWithEventHandlers($B)
     *
     * @param EventHandlerInterface[] $handlers the event handlers
     * @return EventHandlerGroup that can be used to setup a dependency barrier over the specified event handlers
     */
    public function afterEventHandlers(NoOpStackable $handlers)
    {
        $sequences = new NoOpStackable();
        foreach ($handlers as $handler) {
            $sequences[] = $this->consumerRepository->getSequenceFor($handler);
        }
        return new EventHandlerGroup($this, $this->consumerRepository, $sequences);
    }

    /**
     * Create a group of event processors to be used as a dependency.
     *
     * @param EventProcessorList $processors the event processors
     * @return EventHandlerGroup that can be used to setup a SequenceBarrier over the specified event processors
     */
    public function afterEventProcessors(EventProcessorList $processors)
    {
        foreach ($processors as $processor) {
            $this->consumerRepository->addEventProcessor($processor);
        }
        $group = new EventHandlerGroup($this, $this->consumerRepository, Util::getSequencesFor($processors));
        return $group;
    }

    /**
     * Publish an event to the ring buffer
     *
     * @param EventTranslatorInterface $eventTranslator
     * @param NoOpStackable $args
     * @return void
     */
    public function publishEvent(EventTranslatorInterface $eventTranslator, NoOpStackable $args)
    {
        $this->ringBuffer->publishEvent($eventTranslator, $args);
    }

    /**
     * Calls EventProcessor#halt() on all of the event processors created via this disruptor
     *
     * @return void
     */
    public function halt()
    {
        foreach ($this->consumerRepository as $consumerInfo) {
            /* @var ConsumerInfoInterface $consumerInfo*/
            $consumerInfo->halt();
        }
    }

    /**
     * Waits until all events currently in the disruptor have been processed by all event processors
     * and then halts the processors
     *
     * This method will not shutdown the executor, nor will it await the final termination of the
     * processor threads.
     *
     * @param int $timeout the amount of time in microseconds to wait for all events to be processed
     * @return void
     * @throws Exception\TimeoutException
     */
    public function shutdownWithTimeout($timeout)
    {
        $timeoutAt = microtime(true) + ($timeout / 1000000);
        while ($this->_hasBacklog()) {
            if ($timeout > 0 && microtime(true) > $timeoutAt) {
                throw new Exception\TimeoutException();
            }
            // busy spin
        }
        $this->halt();
    }

    /**
     * Waits until all events currently in the disruptor have been processed by all event processors
     * and then halts the processors.  It is critical that publishing to the ring buffer has stopped
     * before calling this method, otherwise it may never return.
     *
     * This method will not shutdown the executor, nor will it await the final termination of the
     * processor threads.
     *
     * @return void
     */
    public function shutdown()
    {
        try {
            $this->shutdownWithTimeout(-1);
        } catch (Exception\TimeoutException $e) {
            $this->exceptionHandler->handleOnShutdownException($e);
        }
    }

    /**
     * The RingBuffer used by this Disruptor.  This is useful for creating custom
     * event processors if the behaviour of BatchEventProcessor is not suitable.
     *
     * Usually called after start() to retrieve the ringbuffer
     *
     * @return RingBuffer
     */
    public function getRingBuffer()
    {
        return $this->ringBuffer;
    }

    /**
     * Get the value of the cursor indicating the published sequence.
     *
     * @return int value of the cursor for events that have been published.
     */
    public function getCursor()
    {
        return $this->ringBuffer->getCursor();
    }

    /**
     * The capacity of the data structure to hold entries.
     *
     * @return int the size of the RingBuffer.
     */
    public function getBufferSize()
    {
        return $this->ringBuffer->getBufferSize();
    }

    /**
     * Get the event for a given sequence in the RingBuffer.
     *
     * @param int $sequence for the event.
     * @return object event for the sequence.
     */
    public function get($sequence)
    {
        return $this->ringBuffer->get($sequence);
    }

    /**
     * Get the SequenceBarrier used by a specific handler. Note that the SequenceBarrier
     * may be shared by multiple event handlers.
     *
     * @param EventHandlerInterface $handler the handler to get the barrier for.
     * @return SequenceBarrierInterface the SequenceBarrier used by handler.
     */
    public function getBarrierFor(EventHandlerInterface $handler)
    {
        return $this->consumerRepository->getBarrierFor($handler);
    }

    /**
     * Confirms if all messages have been consumed by all event processors
     *
     * @return bool
     */
    public function _hasBacklog() // public for pthreads reasons
    {
        $cursor = $this->ringBuffer->getCursor();
        foreach ($this->consumerRepository->getLastSequenceInChain(false) as $consumer) {
            if ($cursor > $consumer->get()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create event processors
     *
     * @param SequenceList|null $barrierSequences
     * @param EventHandlerList $eventHandlers
     * @return EventHandlerGroup
     */
    public function createEventProcessors(SequenceList $barrierSequences = null, EventHandlerList $eventHandlers)
    {
        $this->_checkNotStarted();
        $processorSequences = new SequenceList();
        $barrier = $this->ringBuffer->newBarrier($barrierSequences);

        foreach ($eventHandlers as $eventHandler) {
            $batchEventProcessor = new BatchEventProcessor(
                $this->ringBuffer->getEventClass(),
                $this->ringBuffer,
                $barrier,
                $eventHandler,
                new \Zend\Log\Writer\Syslog() // @todo bug !!!
            );
            if (null !== $this->exceptionHandler) {
                $batchEventProcessor->setExceptionHandler($this->exceptionHandler);
            }
            $this->consumerRepository->addEventProcessor($batchEventProcessor, $eventHandler, $barrier);
            $processorSequences[] = $batchEventProcessor->getSequence();
        }

        if (count($processorSequences)) {
            $this->consumerRepository->unMarkEventProcessorsAsEndOfChain($barrierSequences);
        }

        return new EventHandlerGroup($this, $this->consumerRepository, $processorSequences);
    }

    /**
     * Create worker pool
     *
     * @param SequenceList|null $barrierSequences
     * @param WorkHandlerList $workHandlers
     * @return EventHandlerGroup
     */
    public function createWorkerPool(SequenceList $barrierSequences = null, WorkHandlerList $workHandlers)
    {
        $sequenceBarrier = $this->ringBuffer->newBarrier($barrierSequences);
        $workerPool = WorkerPool::createFromRingBuffer(
            $this->ringBuffer,
            $sequenceBarrier,
            $this->exceptionHandler,
            $workHandlers
        );
        $this->consumerRepository->addWorkerPool($workerPool, $sequenceBarrier);
        return new EventHandlerGroup($this, $this->consumerRepository, $workerPool->getWorkerSequences());
    }

    /**
     * Check not started
     *
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function _checkNotStarted() // public for pthreads reasons
    {
        if ($this->started) {
            throw new Exception\InvalidArgumentException(
                'All event handlers must be added before calling starts'
            );
        }
    }

    public function _checkOnlyStartedOnce() // public for pthreads reasons
    {
        // @todo: locking????
        if (!$this->started) {
            $this->started = true;
        } else {
            throw new Exception\InvalidArgumentException(
                'Disruptor.start() must only be called once'
            );
        }
    }
}
