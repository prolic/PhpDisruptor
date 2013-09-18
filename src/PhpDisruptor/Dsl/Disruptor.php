<?php

namespace PhpDisruptor\Dsl;

use HumusVolatile\ZendCacheVolatile;
use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use PhpDisruptor\WorkerPool;
use PhpDisruptor\WorkHandlerInterface;
use PhpDisruptor\Util\Util;
use Worker;

/**
 * A DSL-style API for setting up the disruptor pattern around a ring buffer (aka the Builder pattern).
 */
class Disruptor implements EventClassCapableInterface
{
    /**
     * @var RingBuffer
     */
    private $ringBuffer;

    /**
     * @var Worker
     */
    private $worker;

    /**
     * @var ConsumerRepository
     */
    private $consumerRepository;

    /**
     * @var bool
     */
    private $started;

    /**
     * @var ExceptionHandlerInterface
     */
    private $exceptionHandler;

    /**
     * Constructor
     *
     * @param RingBuffer $ringBuffer
     * @param $executor
     */
    protected function __construct(RingBuffer $ringBuffer, $executor)
    {
        $this->ringBuffer = $ringBuffer;
        $this->worker = $executor;
        $this->started = false;
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
     * @param EventHandlerInterface[] $handlers
     * @return EventHandlerGroup that can be used to chain dependencies
     */
    public function handleEventsWithEventHandlers(array $handlers)
    {
        return $this->createEventProcessors(array(), $handlers);
    }

    /**
     * Set up custom event processors to handle events from the ring buffer. The Disruptor will
     * automatically start this processors when #start() is called
     *
     * @param AbstractEventProcessor[] $processors
     * @return EventHandlerGroup that can be used to chain dependencies
     */
    public function handleEventsWithEventProcessors(array $processors)
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
     * @param WorkHandlerInterface[] $workHandlers the work handlers that will process events.
     * @return EventHandlerGroup that can be used to chain dependencies.
     */
    public function handleEventsWithWorkerPool(array $workHandlers)
    {
        return $this->createWorkerPool(array(), $workHandlers);
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
    public function afterEventHandlers(array $handlers)
    {
        $sequences = array();
        foreach ($handlers as $handler) {
            $sequences[] = $this->consumerRepository->getSequenceFor($handler);
        }
        return new EventHandlerGroup($this, $this->consumerRepository, $sequences);
    }

    /**
     * Create a group of event processors to be used as a dependency.
     *
     * @param AbstractEventProcessor[] $processors the event processors
     * @return EventHandlerGroup that can be used to setup a SequenceBarrier over the specified event processors
     */
    public function afterEventProcessors($processors)
    {
        foreach ($processors as $processor) {
            $this->consumerRepository->addEventProcessor($processor);
        }
        return new EventHandlerGroup($this, $this->consumerRepository, Util::getSequencesFor($processors));
    }

    /**
     * Publish an event to the ring buffer
     *
     * @param EventTranslatorInterface $eventTranslator
     * @param array $args
     * @return void
     */
    public function publishEvent(EventTranslatorInterface $eventTranslator, array $args = array())
    {
        $this->ringBuffer->publishEvent($eventTranslator, $args);
    }

    /**
     * Starts the event processors and returns the fully configured ring buffer.
     *
     * The ring buffer is set up to prevent overwriting any entry that is yet to
     * be processed by the slowest event processor.
     *
     * This method must only be called once after all event processors have been added.
     *
     * @return RingBuffer the configured ring buffer.
     */
    public function start()
    {
        $gaitingSequences = $this->consumerRepository->getLastSequenceInChain(true);
        $this->ringBuffer->addGatingSequences($gaitingSequences);

        $this->checkOnlyStartedOnce();

        foreach ($this->consumerRepository as $consumerInfo) {
            /* @var ConsumerInfoInterface $consumerInfo*/
            $consumerInfo->start($this->worker);
        }

        return $this->ringBuffer;
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
     * and then halts the processors.  It is critical that publishing to the ring buffer has stopped
     * before calling this method, otherwise it may never return.
     *
     * This method will not shutdown the executor, nor will it await the final termination of the
     * processor threads.
     *
     * @param int $timeout
     * @return void
     */
    public function shutdown($timeout)
    {
        try {
            //@todo: implement
            //shutdown(-1, TimeUnit.MILLISECONDS);
        } catch (Exception\TimeoutException $e) {
            $this->exceptionHandler->handleOnShutdownException($e);
        }

        /*
         * with timeout:
         *
         * long timeOutAt = System.currentTimeMillis() + timeUnit.toMillis(timeout);
            while (hasBacklog())
            {
                if (timeout >= 0 && System.currentTimeMillis() > timeOutAt)
                {
                    throw TimeoutException.INSTANCE;
                }
                // Busy spin
            }
            halt();
         */
    }

    /**
     * The RingBuffer used by this Disruptor.  This is useful for creating custom
     * event processors if the behaviour of BatchEventProcessor is not suitable.
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
    private function hasBacklog()
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
     * @param Sequence[] $barrierSequences
     * @param EventHandlerInterface[] $eventHandlers
     * @return EventHandlerGroup
     */
    public function createEventProcessors(array $barrierSequences, array $eventHandlers)
    {
        $this->checkNotStarted();
        $processorSequences = array();
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
     * @param Sequence[] $barrierSequences
     * @param WorkHandlerInterface[] $workHandlers
     * @return EventHandlerGroup
     */
    public function createWorkerPool(array $barrierSequences, array $workHandlers)
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
    private function checkNotStarted()
    {
        if ($this->started) {
            throw new Exception\InvalidArgumentException(
                'All event handlers must be added before calling starts'
            );
        }
    }

    private function checkOnlyStartedOnce()
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
