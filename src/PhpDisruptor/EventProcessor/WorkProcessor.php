<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use PhpDisruptor\LifecycleAwareInterface;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WorkHandlerInterface;

final class WorkProcessor extends AbstractEventProcessor
{
    /**
     * @var bool
     */
    public $running;

    /**
     * @var Sequence
     */
    public $sequence;

    /**
     * @var RingBuffer
     */
    public $ringBuffer;

    /**
     * @var SequenceBarrierInterface
     */
    public $sequenceBarrier;

    /**
     * @var WorkHandlerInterface
     */
    public $workHandler;

    /**
     * @var ExceptionHandlerInterface
     */
    public $exceptionHandler;

    /**
     * @var Sequence
     */
    public $workSequence;

    /**
     * Constructor
     *
     * @param RingBuffer $ringBuffer
     * @param SequenceBarrierInterface $sequenceBarrier
     * @param WorkHandlerInterface $workHandler
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param Sequence $workSequence
     * @throws Exception\InvalidArgumentException if workhandler and ringbuffer eventclass doesn't match
     */
    public function __construct(
        RingBuffer $ringBuffer,
        SequenceBarrierInterface $sequenceBarrier,
        WorkHandlerInterface $workHandler,
        ExceptionHandlerInterface $exceptionHandler,
        Sequence $workSequence
    ) {
        if ($workHandler->getEventClass() != $this->ringBuffer->getEventClass()) {
            throw new Exception\InvalidArgumentException(
                'All work handlers must use the event class as the ring buffer, buffer has "'
                . $this->ringBuffer->getEventClass() . '" and current handler has "'
                . $workHandler->getEventClass() . '"'
            );
        }
        $this->sequence = new Sequence();
        $this->running = false;
        $this->ringBuffer = $ringBuffer;
        $this->sequenceBarrier = $sequenceBarrier;
        $this->workHandler = $workHandler;
        $this->exceptionHandler = $exceptionHandler;
        $this->workSequence = $workSequence;
    }

    /**
     * Get a reference to the Sequence being used by this EventProcessor.
     *
     * @return Sequence reference to the Sequence for this EventProcessor
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Signal that this EventProcessor should stop when it has finished consuming at the next clean break.
     * It will call {@link SequenceBarrierInterface#alert()} to notify the thread to check status.
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    public function halt()
    {
        if (!$this->casMember('running', false, true)) {
            throw new Exception\RuntimeException(
                'Thread is already running'
            );
        }
        $this->sequenceBarrier->clearAlert();
        $this->_notifyStart();

        $processedSequence = true;
        $cachedAvailableSequence = - PHP_INT_MAX - 1;
        $nextSequence = $this->sequence->get();
        $event = null;
        while (true) {
            try {
                // if previous sequence was processed - fetch the next sequence and set
                // that we have successfully processed the previous sequence
                // typically, this will be true
                // this prevents the sequence getting too far forward if an exception
                // is thrown from the WorkHandler
                if ($processedSequence) {
                    $processedSequence = false;
                    $nextSequence = $this->workSequence->incrementAndGet();
                    $this->sequence->set($nextSequence - 1);
                }
                if ($cachedAvailableSequence >= $nextSequence) {
                    $event = $this->ringBuffer->get($nextSequence);
                    $this->workHandler->onEvent($event);
                    $processedSequence = true;
                } else {
                    $cachedAvailableSequence = $this->sequenceBarrier->waitFor($nextSequence);
                }
            } catch (Exception\AlertException $e) {
                if (!$this->running) {
                    break;
                }
            } catch (\Exception $e) {
                $this->exceptionHandler->handleEventException($e, $nextSequence, $event);
                $processedSequence = true;
            }
        }

        $this->_notifyShutdown();
        $this->running = false;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->running;
    }

    /**
     * @return void
     * @throws Exception\RuntimeException
     */
    public function run()
    {
        if (!$this->casMember('running', false, true)) {
            throw new Exception\RuntimeException(
                'Thread is already running'
            );
        }

        $this->sequenceBarrier->clearAlert();
        $this->_notifyStart();

        $processedSequence = true;
        $cachedAvailableSequence = - PHP_INT_MAX - 1;
        $nextSequence = $this->sequence->get();
        $event = null;

        while (true) {
            try {
                // if previous sequence was processed - fetch the next sequence and set
                // that we have successfully processed the previous sequence
                // typically, this will be true
                // this prevents the sequence getting too far forward if an exception
                // is thrown from the WorkHandler
                if ($processedSequence) {
                    $processedSequence = false;
                    $nextSequence = $this->workSequence->incrementAndGet();
                    $this->sequence->set($nextSequence - 1);
                }

                if ($cachedAvailableSequence >= $nextSequence) {
                    $event = $this->ringBuffer->get($nextSequence);
                    $this->workHandler->onEvent($event);
                    $processedSequence = true;
                } else {
                    $cachedAvailableSequence = $this->sequenceBarrier->waitFor($nextSequence);
                }
            } catch (Exception\AlertException $e) {
                if (!$this->running) {
                    break;
                }
            } catch (\Exception $e) {
                $this->exceptionHandler->handleEventException($e, $nextSequence, $event);
                $processedSequence = true;
            }
        }

        $this->_notifyShutdown();
        $this->running = false;
    }

    /**
     * @return void
     */
    public function _notifyStart() // private !! only public for pthreads reasons
    {
        if ($this->workHandler instanceof LifecycleAwareInterface) {
            try {
                $this->workHandler->onStart();
            } catch (\Exception $e) {
                $this->exceptionHandler->handleOnStartException($e);
            }
        }
    }

    /**
     * @return void
     */
    public function _notifyShutdown() // private !! only public for pthreads reasons
    {
        if ($this->workHandler instanceof LifecycleAwareInterface) {
            try {
                $this->workHandler->onShutdown();
            } catch (\Exception $e) {
                $this->exceptionHandler->handleOnShutdownException($e);
            }
        }
    }
}
