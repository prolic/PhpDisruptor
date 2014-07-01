<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\DataProviderInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use PhpDisruptor\ExceptionHandler\FatalExceptionHandler;
use ConcurrentPhpUtils\CasThreadedMemberTrait;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\LifecycleAwareInterface;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceReportingEventHandlerInterface;
use PhpDisruptor\SequencerInterface;
use PhpDisruptor\TimeoutHandlerInterface;

/**
 * Convenience class for handling the batching semantics of consuming entries from a RingBuffer
 * and delegating the available events to an EventHandler.
 *
 * If the EventHandler also implements LifecycleAware it will be notified just after the thread
 * is started and just before the thread is shutdown.
 */
final class BatchEventProcessor extends AbstractEventProcessor
{
    use CasThreadedMemberTrait;

    /**
     * @var DataProviderInterface
     */
    public $dataProvider;

    /**
     * @var string
     */
    public $eventClass;

    /**
     * @var ExceptionHandlerInterface
     */
    public $exceptionHandler;

    /**
     * @var SequenceBarrierInterface
     */
    public $sequencerBarrier;

    /**
     * @var EventHandlerInterface
     */
    public $eventHandler;

    /**
     * @var Sequence
     */
    public $sequence;

    /**
     * @var TimeoutHandlerInterface|null
     */
    public $timeoutHandler;

    /**
     * @var bool
     */
    public $running;

    /**
     * Constructor
     *
     * @param string $eventClass
     * @param DataProviderInterface $dataProvider
     * @param SequenceBarrierInterface $sequenceBarrier
     * @param EventHandlerInterface $eventHandler
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        $eventClass,
        DataProviderInterface $dataProvider,
        SequenceBarrierInterface $sequenceBarrier,
        EventHandlerInterface $eventHandler
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

        if ($eventHandler instanceof SequenceReportingEventHandlerInterface) {
            $eventHandler->setSequenceCallback($this->sequence);
        }
        $this->eventHandler = $eventHandler;

        $this->exceptionHandler = new FatalExceptionHandler('/tmp/disruptor-batchevents');
        $this->timeoutHandler = ($eventHandler instanceof TimeoutHandlerInterface) ? $eventHandler : null;
        $this->running = false;
    }

    /**
     * @return Sequence
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @inheritdoc
     */
    public function halt()
    {
        $this->running = false;
        $this->sequencerBarrier->alert();
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
        if (!$this->casMember('running', false, true)) {
            throw new Exception\RuntimeException(
                'Thread is already running'
            );
        }

        $this->sequencerBarrier->clearAlert();

        // notify start
        if ($this->eventHandler instanceof LifecycleAwareInterface) {
            try {
                $this->eventHandler->onStart();
            } catch (\Exception $e) {
                $this->exceptionHandler->handleOnStartException($e);
            }
        }

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
                // notify timeout
                $availableSequence = $this->getSequence()->get();
                try {
                    if (null !== $this->timeoutHandler) {
                        $this->timeoutHandler->onTimeout($availableSequence);
                    }
                } catch (\Exception $e) {
                    $this->exceptionHandler->handleEventException($e, $availableSequence, null);
                }
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

        // notify shutdown
        if ($this->eventHandler instanceof LifecycleAwareInterface) {
            try {
                $this->eventHandler->onShutdown();
            } catch (\Exception $e) {
                $this->exceptionHandler->handleOnShutdownException($e);
            }
        }
        $this->running = false;
    }
}
