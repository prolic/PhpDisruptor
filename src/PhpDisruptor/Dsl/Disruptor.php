<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\EventProcessor\EventProcessorInterface;
use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandlerInterface;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\WorkHandlerInterface;
use PhpDisruptor\Util\Util;
use Zend\Cache\Storage\StorageInterface;

/*
import java.util.concurrent.Executor;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicBoolean;

import com.lmax.disruptor.BatchEventProcessor;
import com.lmax.disruptor.EventFactory;
import com.lmax.disruptor.EventHandler;
import com.lmax.disruptor.EventProcessor;
import com.lmax.disruptor.EventTranslator;
import com.lmax.disruptor.EventTranslatorOneArg;
import com.lmax.disruptor.ExceptionHandler;
import com.lmax.disruptor.RingBuffer;
import com.lmax.disruptor.Sequence;
import com.lmax.disruptor.SequenceBarrier;
import com.lmax.disruptor.TimeoutException;
import com.lmax.disruptor.WaitStrategy;
import com.lmax.disruptor.WorkHandler;
import com.lmax.disruptor.WorkerPool;
import com.lmax.disruptor.util.Util;
*/
use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;

/**
* A DSL-style API for setting up the disruptor pattern around a ring buffer (aka the Builder pattern).
*
* A simple example of setting up the disruptor with two event handlers that must process events in order:
*
* <pre><code> Disruptor&lt;MyEvent&gt; disruptor = new Disruptor&lt;MyEvent&gt;(MyEvent.FACTORY, 32, Executors.newCachedThreadPool());
        * EventHandler&lt;MyEvent&gt; handler1 = new EventHandler&lt;MyEvent&gt;() { ... };
        * EventHandler&lt;MyEvent&gt; handler2 = new EventHandler&lt;MyEvent&gt;() { ... };
        * disruptor.handleEventsWith(handler1);
        * disruptor.after(handler1).handleEventsWith(handler2);
        *
        * RingBuffer ringBuffer = disruptor.start();</code></pre>
*
* @param <T> the type of event used.
    */

class Disruptor implements EventClassCapableInterface
{
    /**
     * @var RingBuffer
     */
    protected $ringBuffer;

    protected $executor;

    /**
     * @var ConsumerRepository
     */
    protected $consumerRepository;

    /**
     * @var bool
     */
    protected $started = false; // @todo: make atomic

    /**
     * @var ExceptionHandlerInterface
     */
    protected $exceptionHandler;

    /**
     * Constructor
     *
     * @param RingBuffer $ringBuffer
     * @param $executor
     */
    protected function __construct(RingBuffer $ringBuffer, $executor)
    {
        $this->ringBuffer = $ringBuffer;
        $this->executor = $executor;
    }

    /**
     * Create disruptor instance
     *
     * @param StorageInterface $storage
     * @param EventFactoryInterface $eventFactory
     * @param $ringBufferSize
     * @param $executor
     * @param ProducerType $producerType
     * @param WaitStrategyInterface|null $waitStrategy
     * @return Disruptor
     */
    public static function create(
        StorageInterface $storage,
        EventFactoryInterface $eventFactory,
        $ringBufferSize,
        $executor,
        ProducerType $producerType,
        WaitStrategyInterface $waitStrategy = null
    ) {
        $ringBuffer = RingBuffer::create($storage, $producerType, $eventFactory, $ringBufferSize, $waitStrategy);
        return static::createFromRingBuffer($ringBuffer, $executor);
    }

    /**
     * @param RingBuffer $ringBuffer
     * @param $executor
     * @return Disruptor
     */
    public static function createFromRingBuffer(RingBuffer $ringBuffer, $executor)
    {
        return new static($ringBuffer, $executor);
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
     * @param EventProcessorInterface[] $processors
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
     * @param EventProcessorInterface[] $processors the event processors
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
            $consumerInfo->start($this->executor);
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


}
