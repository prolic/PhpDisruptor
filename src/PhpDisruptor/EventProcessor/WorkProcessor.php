<?php

namespace PhpDisruptor\EventProcessor;

use HumusVolatile\ZendCacheVolatile;
use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use PhpDisruptor\LifecycleAwareInterface;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WorkHandlerInterface;

final class WorkProcessor implements EventProcessorInterface
{
    /**
     * @var ZendCacheVolatile
     */
    private $running;

    /**
     * @var Sequence
     */
    private $sequence;

    /**
     * @var RingBuffer
     */
    private $ringBuffer;

    /**
     * @var SequenceBarrierInterface
     */
    private $sequenceBarrier;

    /**
     * @var WorkHandlerInterface
     */
    private $workHandler;

    /**
     * @var ExceptionHandlerInterface
     */
    private $exceptionHandler;

    /**
     * @var Sequence
     */
    private $workSequence;

    /**
     * Constructor
     *
     * @param RingBuffer $ringBuffer
     * @param SequenceBarrierInterface $sequenceBarrier
     * @param WorkHandlerInterface $workHandler
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param Sequence $workSequence
     */
    public function __construct(
        RingBuffer $ringBuffer,
        SequenceBarrierInterface $sequenceBarrier,
        WorkHandlerInterface $workHandler,
        ExceptionHandlerInterface $exceptionHandler,
        Sequence $workSequence
    ) {
        $storage = $ringBuffer->getStorage();
        $this->sequence = new Sequence($storage);
        $this->running = new ZendCacheVolatile($storage, get_class($this) . '::running', false);
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
        if (!$this->running->compareAndSwap(false, true)) {
            throw new Exception\RuntimeException(
                'Thread is already running'
            );
        }
        $this->sequenceBarrier->clearAlert();
        $this->notifyStart();

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
                if (!$this->running->get()) {
                    break;
                }
            } catch (\Exception $e) {
                $this->exceptionHandler->handleEventException($e, $nextSequence, $event);
                $processedSequence = true;
            }
        }

        $this->notifyShutdown();
        $this->running->set(false);
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->running->get();
    }

    /**
     * @return void
     * @throws Exception\RuntimeException
     */
    public function run()
    {
        if (!$this->running->compareAndSwap(false, true)) {
            throw new Exception\RuntimeException(
                'Thread is already running'
            );
        }

        $this->sequenceBarrier->clearAlert();
        $this->notifyStart();

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
                if (!$this->running->get()) {
                    break;
                }
            } catch (\Exception $e) {
                $this->exceptionHandler->handleEventException($e, $nextSequence, $event);
                $processedSequence = true;
            }
        }

        $this->notifyShutdown();
        $this->running->set(false);
    }

    /**
     * @return void
     */
    private function notifyStart()
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
    private function notifyShutdown()
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
