<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\DataProviderInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandlerInterface;
use PhpDisruptor\FatalExceptionHandler;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\LifecycleAwareInterface
use PhpDisruptor\Sequence;
use PhpDisruptor\SequencerInterface;
use PhpDisruptor\TimeoutHandlerInterface;
use Zend\Log\LoggerInterface;

/**
 * Convenience class for handling the batching semantics of consuming entries from a RingBuffer
 * and delegating the available events to an EventHandler.
 *
 * If the EventHandler also implements LifecycleAware it will be notified just after the thread
 * is started and just before the thread is shutdown.
 */
class BatchEventProcessor implements EventProcessorInterface
{
    /**
     * @var bool
     */
    protected $running = false; // @todo: save in storage ???

    /**
     * @var DataProviderInterface
     */
    protected $dataProvider;

    /**
     * @var string
     */
    protected $eventClass;

    /**
     * @var ExceptionHandlerInterface
     */
    protected $exceptionHandler;

    /**
     * @var SequenceBarrierInterface
     */
    protected $sequencerBarrier;

    /**
     * @var EventHandlerInterface
     */
    protected $eventHandler;

    /**
     * @var Sequence
     */
    protected $sequence;

    /**
     * @var TimeoutHandlerInterface|null
     */
    protected $timeoutHandler;

    /**
     * Constructor
     *
     * @param string $eventClass
     * @param DataProviderInterface $dataProvider
     * @param SequenceBarrierInterface $sequenceBarrier
     * @param EventHandlerInterface $eventHandler
     * @param LoggerInterface $logger
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        $eventClass,
        DataProviderInterface $dataProvider,
        SequenceBarrierInterface $sequenceBarrier,
        EventHandlerInterface $eventHandler,
        LoggerInterface $logger
    ) {
        if (!class_exists($eventClass)) {
            throw new Exception\InvalidArgumentException(
                'event class "' . $eventClass . '" does not exist'
            );
        }
        if ($dataProvider->getEventClass() != $eventClass) {
            throw new Exception\InvalidArgumentException(
                'invalid data provider given, must use the event class: "' . $eventClass . '"'
            );
        }
        if ($eventHandler->getEventClass() != $eventClass) {
            throw new Exception\InvalidArgumentException(
                'invalid event handler given, must use the event class: "' . $eventClass . '"'
            );
        }

        $this->eventClass = $eventClass;
        $this->dataProvider = $dataProvider;
        $this->sequencerBarrier = $sequenceBarrier;
        $this->sequence = new Sequence(SequencerInterface::INITIAL_CURSOR_VALUE);
        $this->exceptionHandler = new FatalExceptionHandler($logger);

        $this->timeoutHandler = ($eventHandler instanceof TimeoutHandlerInterface) ? $eventHandler : null;
    }

    /**
     * @return Sequence
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    public function halt()
    {
        $this->running = false;
        $this->sequencerBarrier->alert();
    }

    public function isRunning()
    {
        return $this->running;
    }

    /**
     * @param ExceptionHandlerInterface $exceptionHandler
     */
    public function setExceptionHandler(ExceptionHandlerInterface $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
    }

    public function run()
    {
        if (true === $this->running) {
            throw new Exception\RuntimeException('already running');
        }
        $this->sequencerBarrier->clearAlert();
        $this->notifyStart();
        $nextSequence = $this->getSequence()->get() + 1;
        while (true) {
            try {
                $availableSequence = $this->sequencerBarrier->waitFor($nextSequence);
                while ($nextSequence <= $availableSequence) {
                    $event = $this->dataProvider->get($nextSequence);
                    $this->eventHandler->onEvent($event, $nextSequence, $nextSequence == $availableSequence);
                    $nextSequence++;
                }
                $this->getSequence()->set($availableSequence);
            } catch (Exception\TimeoutException $e) {
                $this->notifyTimeout($this->getSequence()->get());
            } catch (Exception\AlertException $e) {
                if (!$this->running) {
                    break;
                }
            } catch (\Exception $e) {
                $event = isset($event) ? $event : '';
                $this->exceptionHandler->handleEventException($e, $nextSequence, $event);
                $this->getSequence()->set($nextSequence);
                $nextSequence++;
            }
        }
        $this->notifyShutdown();
        $this->running = false;
    }

    /**
     * @param $availableSequence
     * @return void
     */
    protected function notifyTimeout($availableSequence)
    {
        try {
            if (null !== $this->timeoutHandler) {
                $this->timeoutHandler->onTimeout($availableSequence);
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handleEventException($e, $availableSequence, null);
        }
    }

    /**
     * Notifies the EventHandler when this processor is starting up
     *
     * @return void
     */
    protected function notifyStart()
    {
        if ($this->eventHandler instanceof LifecycleAwareInterface) {
            try {
                $this->eventHandler->onStart();
            } catch (\Exception $e) {
                $this->exceptionHandler->handleOnStartException($e);
            }
        }
    }

    /**
     * Notifies the EventHandler immediately prior to this processor shutting down
     *
     * @return void
     */
    protected function notifyShutdown()
    {
        if ($this->eventHandler instanceof LifecycleAwareInterface) {
            try {
                $this->eventHandler->onShutdown();
            } catch (\Exception $e) {
                $this->exceptionHandler->handleOnShutdownException($e);
            }
        }
    }
}
