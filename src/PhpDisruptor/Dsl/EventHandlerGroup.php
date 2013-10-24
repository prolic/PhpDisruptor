<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\Lists\EventHandlerList;
use PhpDisruptor\Lists\EventProcessorList;
use PhpDisruptor\Lists\SequenceList;
use PhpDisruptor\Lists\WorkHandlerList;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use Stackable;

class EventHandlerGroup extends Stackable implements EventClassCapableInterface
{
    /**
     * @var string
     */
    public $eventClass;

    /**
     * @var Disruptor
     */
    public $disruptor;

    /**
     * @var ConsumerRepository
     */
    public $consumerRepository;

    /**
     * @var SequenceList
     */
    public $sequences;

    /**
     * Constructor
     *
     * @param Disruptor $disruptor
     * @param ConsumerRepository $consumerRepository
     * @param SequenceList $sequences
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        Disruptor $disruptor,
        ConsumerRepository $consumerRepository,
        SequenceList $sequences
    ) {
        if ($disruptor->getEventClass() != $consumerRepository->getEventClass()) {
            throw new Exception\InvalidArgumentException(
                '$consumerRepository uses event class ' . $consumerRepository->getEventClass()
                . ' but $disruptor uses event class ' . $disruptor->getEventClass()
            );
        }
        $this->disruptor = $disruptor;
        $this->eventClass = $consumerRepository->getEventClass();
        $this->consumerRepository = $consumerRepository;
        $this->sequences = $sequences;
    }

    /**
     * @inheritdoc
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }


    /**
     * Create a new event handler group that combines the consumers in this group with otherHandlerGroup
     *
     * @param EventHandlerGroup $otherHandlerGroup the event handler group to combine
     * @return EventHandlerGroup a new EventHandlerGroup combining the existing and new consumers
     */
    public function andEventHandlerGroup(self $otherHandlerGroup)
    {
        $combinedSequences = new SequenceList();
        $combinedSequences->merge($this->sequences);
        $combinedSequences->merge($otherHandlerGroup->sequences);
        return new self($this->disruptor, $this->consumerRepository, $combinedSequences);
    }

    /**
     * Create a new event handler group that combines the handlers in this group with <tt>processors</tt>.
     *
     * @param EventProcessorList $processors the processors to combine.
     * @return EventHandlerGroup a new EventHandlerGroup combining the existing and new processors
     */
    public function andProcessors(EventProcessorList $processors)
    {
        $combinedSequences = new SequenceList();
        foreach ($processors as $processor) {
            $this->consumerRepository->addEventProcessor($processor);
            $combinedSequences[] = $processor->getSequence();
        }
        $combinedSequences->merge($this->sequences);
        return new self($this->disruptor, $this->consumerRepository, $combinedSequences);
    }

    /**
     * Set up batch handlers to consume events from the ring buffer. These handlers will only process events
     * after every EventProcessor in this group has processed the event.
     *
     * This method is generally used as part of a chain. For example if the handler "A" must
     * process events before handler "B":
     *
     * $dw->handleEventsWith($A)->then($B);
     *
     * @param EventHandlerList $handlers the batch handlers that will process events.
     * @return EventHandlerGroup that can be used to set up a event processor barrier over the created event processors.
     */
    public function then(EventHandlerList $handlers)
    {
        return $this->handleEventsWith($handlers);
    }

    /**
     * Set up a worker pool to handle events from the ring buffer. The worker pool will only process events
     * after every EventProcessor in this group has processed the event. Each event will be processed
     * by one of the work handler instances.
     *
     * This method is generally used as part of a chain. For example if the handler "A"
     * process events before the worker pool with handlers "B", "C":
     *
     * $dw->handleEventsWith($A)->thenHandleEventsWithWorkerPool($B, $C);
     *
     * @param WorkHandlerList $handlers the work handlers that will process events.
     * Each work handler instance will provide an extra thread in the worker pool.
     * @return EventHandlerGroup that can be used to set up a event processor barrier over the created event processors
     */
    public function thenHandleEventsWithWorkerPool(WorkHandlerList $handlers)
    {
        return $this->handleEventsWithWorkerPool($handlers);
    }

    /**
     * Set up batch handlers to handle events from the ring buffer. These handlers will only process events
     * after every EventProcessor in this group has processed the event.
     *
     * This method is generally used as part of a chain. For example if the handler "A" must
     * process events before handler "B":
     *
     * $dw->after($A)->handleEventsWith($B);
     *
     * @param EventHandlerList $handlers the batch handlers that will process events.
     * @return EventHandlerGroup that can be used to set up a event processor barrier over the created event processors.
     */
    public function handleEventsWith(EventHandlerList $handlers)
    {
        return $this->disruptor->createEventProcessors($this->sequences, $handlers);
    }

    /**
     * Set up a worker pool to handle events from the ring buffer. The worker pool will only process events
     * after every EventProcessor in this group has processed the event. Each event will be processed
     * by one of the work handler instances.
     *
     * This method is generally used as part of a chain. For example if the handler <code>A</code> must
     * process events before the worker pool with handlers <code>B, C</code>:
     *
     * <pre><code>dw.after(A).handleEventsWithWorkerPool(B, C);</code></pre>
     *
     * @param WorkHandlerList $handlers the work handlers that will process events.
     * Each work handler instance will provide an extra thread in the worker pool.
     * @return EventHandlerGroup that can be used to set up a event processor barrier over the created event processors.
     */
    public function handleEventsWithWorkerPool(WorkHandlerList $handlers)
    {
        return $this->disruptor->createWorkerPool($this->sequences, $handlers);
    }

    /**
     * Create a dependency barrier for the processors in this group.
     * This allows custom event processors to have dependencies on
     * BatchEventProcessors created by the disruptor.
     *
     * @return SequenceBarrierInterface including all the processors in this group.
     */
    public function asSequenceBarrier()
    {
        return $this->disruptor->getRingBuffer()->newBarrier($this->sequences);
    }
}
