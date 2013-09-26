<?php

namespace PhpDisruptor;

use PhpDisruptor\Dsl\ProducerType;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\WaitStrategy\BlockingWaitStrategy;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use Stackable;

/**
 * Ring based store of reusable entries containing the data representing
 * an event being exchanged between event producer and EventProcessors.
 */
final class RingBuffer extends Stackable implements CursoredInterface, DataProviderInterface
{
    /**
     * @var int
     */
    public $indexMask;

    /**
     * @var object[]
     */
    public $entries;

    /**
     * @var int
     */
    public $bufferSize;

    /**
     * @var SequencerInterface
     */
    public $sequencer;

    /**
     * @var string
     */
    public $eventClass;

    /**
     * @var EventFactoryInterface
     */
    public $eventFactory;

    /**
     * Construct a RingBuffer with the full option set.
     *
     * @param EventFactoryInterface $eventFactory to newInstance entries for filling the RingBuffer
     * @param SequencerInterface $sequencer to handle the ordering of events moving through the RingBuffer.
     */
    protected function __construct(EventFactoryInterface $eventFactory, SequencerInterface $sequencer)
    {
        $this->eventFactory = $eventFactory;
        $this->sequencer = $sequencer;
    }

    public function run()
    {
        $this->bufferSize = $this->sequencer->getBufferSize();
        $this->indexMask = $this->bufferSize - 1;
        $this->eventClass = $this->eventFactory->getEventClass();
        $bufferSize = $this->sequencer->getBufferSize();
        $this->entries = new StackableArray();
        for ($i = 0; $i < $bufferSize; $i++) {
            $this->entries[$i] = $this->eventFactory->newInstance();
        }
    }

    /**
     * Get the event class
     *
     * @return string
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * Create a new multiple producer RingBuffer with the specified wait strategy.
     *
     * @param EventFactoryInterface $factory used to create the events within the ring buffer.
     * @param int $bufferSize number of elements to create within the ring buffer.
     * @param WaitStrategyInterface|null $waitStrategy used, default: BlockingWaitStrategy
     * @return RingBuffer
     */
    public static function createMultiProducer(
        EventFactoryInterface $factory,
        $bufferSize,
        WaitStrategyInterface $waitStrategy = null
    ) {
        if (null === $waitStrategy) {
            $waitStrategy = new BlockingWaitStrategy();
        }
        $sequencer = new MultiProducerSequencer($bufferSize, $waitStrategy);
        $ringBuffer = new self($factory, $sequencer);
        $ringBuffer->run();
        return $ringBuffer;
    }

    /**
     * Create a new single producer RingBuffer with the specified wait strategy.
     *
     * @param EventFactoryInterface $factory used to create the events within the ring buffer.
     * @param int $bufferSize number of elements to create within the ring buffer.
     * @param WaitStrategyInterface|null $waitStrategy used, default: BlockingWaitStrategy
     * @return RingBuffer
     */
    public static function createSingleProducer(
        EventFactoryInterface $factory,
        $bufferSize,
        WaitStrategyInterface $waitStrategy = null
    ) {
        if (null === $waitStrategy) {
            $waitStrategy = new BlockingWaitStrategy();
        }
        $sequencer = new SingleProducerSequencer($bufferSize, $waitStrategy);
        $ringBuffer = new self($factory, $sequencer);
        $ringBuffer->run();
        return $ringBuffer;
    }

    /**
     * Create a new Ring Buffer with the specified producer type (SINGLE or MULTI)
     *
     * @param ProducerType $producerType
     * @param EventFactoryInterface $eventFactory
     * @param int $bufferSize
     * @param WaitStrategyInterface|null $waitStrategy
     * @return RingBuffer
     */
    public static function create(
        ProducerType $producerType,
        EventFactoryInterface $eventFactory,
        $bufferSize,
        WaitStrategyInterface $waitStrategy = null
    ) {
        switch ($producerType) {
            case ProducerType::SINGLE:
                $ringBuffer = self::createSingleProducer($eventFactory, $bufferSize, $waitStrategy);
                break;
            case ProducerType::MULTI:
                $ringBuffer = self::createMultiProducer($eventFactory, $bufferSize, $waitStrategy);
                break;
        }
        $ringBuffer->run();
        return $ringBuffer;
    }

    /**
     * Get the event for a given sequence in the RingBuffer.
     *
     * This call has 2 uses.  Firstly use this call when publishing to a ring buffer.
     * After calling {@link RingBuffer::next()} use this call to get hold of the
     * preallocated event to fill with data before calling {@link RingBuffer#publish(long)}.
     *
     * Secondly use this call when consuming data from the ring buffer.  After calling
     * {@link SequenceBarrierInterface#waitFor(long)} call this method with any value greater than
     * that your current consumer sequence and less than or equal to the value returned from
     * the {@link SequenceBarrierInterface#waitFor(long)} method.
     *
     * @param int $sequence
     * @return object the event for the given sequence
     * @throws Exception\InvalidArgumentException
     */
    public function get($sequence)
    {
        return $this->entries[(int) $sequence & $this->indexMask];
    }

    /**
     * Increment and return the next sequence for the ring buffer.  Calls of this
     * method should ensure that they always publish the sequence afterward.  E.g.
     *
     * $sequence = $ringBuffer->next();
     * try {
     *     $event = $ringBuffer->get($sequence);
     *     // Do some work with the event.
     * } finally {
     *     $ringBuffer->publish($sequence);
     * }
     *
     * @param int $n
     * @return int The next sequence to publish to.
     */
    public function next($n = 1)
    {
        return $this->sequencer->next($n);
    }

    /**
     * Increment and return the next sequence for the ring buffer.  Calls of this
     * method should ensure that they always publish the sequence afterward.  E.g.
     *
     * $sequence = $ringBuffer->next();
     * try {
     *     $event = $ringBuffer->get($sequence);
     *     // Do some work with the event.
     * } finally {
     *     $ringBuffer->publish($sequence);
     * }
     *
     * This method will not block if there is not space available in the ring
     * buffer, instead it will throw an InsufficientCapacityException.
     *
     * @param int $n
     * @return int The next sequence to publish to.
     * @throws Exception\InsufficientCapacityException if the necessary space in the ring buffer is not available
     */
    public function tryNext($n = 1)
    {
        return $this->sequencer->tryNext($n);
    }

    /**
     * Resets the cursor to a specific value.  This can be applied at any time, but it is worth not
     * that it is a racy thing to do and should only be used in controlled circumstances.  E.g. during
     * initialisation.
     *
     * @param int $sequence The sequence to reset too.
     * @return void
     * @throws Exception\RuntimeException If any gating sequences have already been specified.
     */
    public function resetTo($sequence)
    {
        $this->sequencer->claim($sequence);
        $this->sequencer->publish($sequence);
    }

    /**
     * Sets the cursor to a specific sequence and returns the preallocated entry that is stored there.  This
     * is another deliberately racy call, that should only be done in controlled circumstances, e.g. initialisation.
     *
     * @param int $sequence The sequence to claim.
     * @return object The preallocated event.
     */
    public function claimAndGetPreallocated($sequence)
    {
        $this->sequencer->claim($sequence);
        return $this->get($sequence);
    }

    /**
     * Determines if a particular entry has been published.
     *
     * @param int $sequence The sequence to identify the entry.
     * @return bool If the value has been published or not.
     */
    public function isPublished($sequence)
    {
        return $this->sequencer->isAvailable($sequence);
    }

    /**
     * Add the specified gating sequences to this instance of the Disruptor.  They will
     * safely and atomically added to the list of gating sequences.
     *
     * @param Sequence[] $gatingSequences The sequences to add.
     * @return void
     */
    public function addGatingSequences(StackableArray $gatingSequences)
    {
        $this->sequencer->addGatingSequences($gatingSequences);
    }

    /**
     * Get the minimum sequence value from all of the gating sequences
     * added to this ringBuffer.
     *
     * @return int The minimum gating sequence or the cursor sequence if
     * no sequences have been added.
     */
    public function getMinimumGatingSequence()
    {
        return $this->sequencer->getMinimumSequence();
    }

    /**
     * Remove the specified sequence from this ringBuffer.
     *
     * @param Sequence $sequence to be removed.
     * @return bool <tt>true</tt> if this sequence was found, <tt>false</tt> otherwise.
     */
    public function removeGatingSequence(Sequence $sequence)
    {
        return $this->sequencer->removeGatingSequence($sequence);
    }

    /**
     * Create a new SequenceBarrier to be used by an EventProcessor to track which messages
     * are available to be read from the ring buffer given a list of sequences to track.
     *
     * @param Sequence[] $sequencesToTrack the additional sequences to track
     * @return SequenceBarrierInterface A sequence barrier that will track the specified sequences.
     */
    public function newBarrier(StackableArray $sequencesToTrack = null)
    {
        if (null === $sequencesToTrack) {
            $sequencesToTrack = new StackableArray();
        }
        return $this->sequencer->newBarrier($sequencesToTrack);
    }

    /**
     * Get the current cursor value for the ring buffer.  The cursor value is
     * the last value that was published, or the highest available sequence
     * that can be consumed.
     *
     * @return int
     */
    public function getCursor()
    {
        return $this->sequencer->getCursor();
    }

    /**
     * The size of the buffer.
     *
     * @return int
     */
    public function getBufferSize()
    {
        return $this->bufferSize;
    }

    /**
     * Given specified <tt>requiredCapacity</tt> determines if that amount of space
     * is available.  Note, you can not assume that if this method returns <tt>true</tt>
     * that a call to {@link RingBuffer#next()} will not block.  Especially true if this
     * ring buffer is set up to handle multiple producers.
     *
     * @param int $requiredCapacity The capacity to check for.
     * @return bool <tt>true</tt> If the specified <tt>requiredCapacity</tt> is available
     * <tt>false</tt> if now.
     */
    public function hasAvailableCapacity($requiredCapacity)
    {
        return $this->sequencer->hasAvailableCapacity($requiredCapacity);
    }

    /**
     * @param EventTranslatorInterface $translator
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    private function checkTranslator(EventTranslatorInterface $translator)
    {
        if ($translator->getEventClass() != $this->getEventClass()) {
            throw new Exception\InvalidArgumentException(
                'Event translator does not match event class, translator has "' . $translator->getEventClass() . '"'
                . ' and Ringbuffer has "' . $this->getEventClass() . '"'
            );
        }
    }

    /**
     * @param EventTranslatorInterface[] $translators
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    private function checkTranslators(array $translators)
    {
        foreach ($translators as $translator) {
            if ($translator->getEventClass() != $this->getEventClass()) {
                throw new Exception\InvalidArgumentException(
                    'Event translator does not match event class, translator has "' . $translator->getEventClass() . '"'
                    . ' and Ringbuffer has "' . $this->getEventClass() . '"'
                );
            }
        }
    }

    /**
     * Publishes an event to the ring buffer.  It handles
     * claiming the next sequence, getting the current (uninitialised)
     * event from the ring buffer and publishing the claimed sequence
     * after translation.
     *
     * @param EventTranslatorInterface $translator The user specified translation for the event
     * @param array $args
     * @return void
     * @throws Exception\InvalidArgumentException if event translator does not match event class
     */
    public function publishEvent(EventTranslatorInterface $translator, array $args = array())
    {
        $this->checkTranslator($translator);
        $this->translateAndPublish($translator, $this->sequencer->next(), $args);
    }

    /**
     * Attempts to publish an event to the ring buffer.  It handles
     * claiming the next sequence, getting the current (uninitialised)
     * event from the ring buffer and publishing the claimed sequence
     * after translation.  Will return false if specified capacity
     * was not available.
     *
     * @param EventTranslatorInterface $translator The user specified translation for the event
     * @param array $args
     * @return bool true if the value was published, false if there was insufficient
     * capacity.
     * @throws Exception\InvalidArgumentException if event translator does not match event class
     */
    public function tryPublishEvent(EventTranslatorInterface $translator, array $args = array())
    {
        $this->checkTranslator($translator);
        try {
            $sequence = $this->sequencer->tryNext();
            $this->translateAndPublish($translator, $sequence, $args);
            return true;
        } catch (Exception\InsufficientCapacityException $e) {
            return false;
        }
    }

    /**
     * Publishes multiple events to the ring buffer.  It handles
     * claiming the next sequence, getting the current (uninitialised)
     * event from the ring buffer and publishing the claimed sequence
     * after translation.
     *
     * @param EventTranslatorInterface[] $translators The user specified translation for each event
     * @param array $args
     * @param int $batchStartsAt
     * @param int $batchSize
     * @return void
     * @throws Exception\InvalidArgumentException if event translator does not match event class
     */
    public function publishEvents(array $translators, array $args = array(), $batchStartsAt = 0, $batchSize = 0)
    {
        $this->checkTranslators($translators);
        $batchSize = $this->calcBatchSize($batchSize, $translators, $args);

        if (empty($args)) {
            $this->checkTranslatorsBounds($translators, $batchStartsAt, $batchSize);
        } else {
            $this->checkArgumentsBounds($args, $batchStartsAt, $batchSize);
        }

        $finalSequence = $this->sequencer->next($batchSize);
        $this->translateAndPublishBatch($translators, $finalSequence, $args, $batchStartsAt, $batchSize);
    }

    /**
     * @param $batchSize
     * @param EventTranslatorInterface[] $translators The user specified translation for each event
     * @param array $args
     * @return int
     */
    private function calcBatchSize($batchSize, array $translators, array $args)
    {
        if (0 != $batchSize) {
            return $batchSize;
        }
        $batchSize = count($args);
        if (0 == $batchSize) {
            $batchSize = count($translators);
        }
        return $batchSize;
    }

    /**
     * Attempts to publish multiple events to the ring buffer.  It handles
     * claiming the next sequence, getting the current (uninitialised)
     * event from the ring buffer and publishing the claimed sequence
     * after translation.  Will return false if specified capacity
     * was not available.
     *
     * @param EventTranslatorInterface[] $translators The user specified translation for each event
     * @param array $args
     * @param int $batchStartsAt
     * @param int|null $batchSize
     * @return bool true if the value was published, false if there was insufficient
     *         capacity.
     * @throws Exception\InvalidArgumentException if event translator does not match event class
     */
    public function tryPublishEvents(array $translators, array $args = array(), $batchStartsAt = 0, $batchSize = null)
    {
        $this->checkTranslators($translators);
        $batchSize = $this->calcBatchSize($batchSize, $translators, $args);

        if (empty($args)) {
            $this->checkTranslatorsBounds($translators, $batchStartsAt, $batchSize);
        } else {
            $this->checkArgumentsBounds($args, $batchStartsAt, $batchSize);
        }

        try {
            $finalSequence = $this->sequencer->tryNext($batchSize);
            $this->translateAndPublishBatch($translators, $finalSequence, $args, $batchStartsAt, $batchSize);
            return true;
        } catch (Exception\InsufficientCapacityException $e) {
            return false;
        }
    }

    /**
     * Publish the specified sequence.  This action marks this particular
     * message as being available to be read.
     *
     * @param int $low the lowest sequence number to be published
     * @param int|null $high the highest sequence number to be published
     * @return void
     */
    public function publish($low, $high = null)
    {
        $this->sequencer->publish($low, $high);
    }

    /**
     * Get the remaining capacity for this ringBuffer.
     *
     * @return int The number of slots remaining.
     */
    public function remainingCapacity()
    {
        return $this->sequencer->remainingCapacity();
    }

    /**
     * @param EventTranslatorInterface[] $translators
     * @param int $batchStartsAt
     * @param int $batchSize
     * @return void
     */
    private function checkTranslatorsBounds(array $translators, $batchStartsAt, $batchSize)
    {
        $this->checkBatchSizing($batchStartsAt, $batchSize);
        $this->batchOverRuns($translators, $batchStartsAt, $batchSize);
    }

    /**
     * @param array $args
     * @param int $batchStartsAt
     * @param int $batchSize
     * @return void
     */
    private function checkArgumentsBounds(array $args, $batchStartsAt, $batchSize)
    {
        $this->checkBatchSizing($batchStartsAt, $batchSize);
        foreach ($args as $arg) {
            $this->batchOverRuns($arg, $batchStartsAt, $batchSize);
        }
    }

    /**
     * @param int $batchStartsAt
     * @param int $batchSize
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    private function checkBatchSizing($batchStartsAt, $batchSize)
    {
        if ($batchStartsAt < 0 || $batchSize < 0) {
            throw new Exception\InvalidArgumentException(
                'Both $batchStartsAt and $batchSize must be positive'
            );
        } elseif ($batchSize > $this->bufferSize) {
            throw new Exception\InvalidArgumentException(
                'The ring buffer cannot accommodate ' . $batchSize
                . ' it only has space for ' . $this->bufferSize . ' entities'
            );
        }
    }

    /**
     * @param array $args
     * @param int $batchStartsAt
     * @param int $batchSize
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    private function batchOverRuns(array $args, $batchStartsAt, $batchSize)
    {
        if ($batchStartsAt + $batchSize > count($args)) {
            throw new Exception\InvalidArgumentException(
                'A batchSize of: ' . $batchSize . ' with batchStartsAt of: ' . $batchStartsAt
                . ' will overrun the available number of arguments: ' . (count($args) - $batchStartsAt)
            );
        }
    }

    /**
     * Translate and publish
     *
     * @param EventTranslatorInterface $translator
     * @param int $sequence
     * @param array $args
     * @return void
     * @throws \Exception
     */
    private function translateAndPublish(EventTranslatorInterface $translator, $sequence, array $args = array())
    {
        try {
            $translator->translateTo($this->get($sequence), $sequence, $args);
        } catch (\Exception $e) {
            $this->sequencer->publish($sequence);
            throw $e;
        }
        $this->sequencer->publish($sequence);
    }

    /**
     * Translate and publish batch
     *
     * @param EventTranslatorInterface $translator
     * @param int $batchStartsAt
     * @param int $batchSize
     * @param int $finalSequence
     * @param array $args
     * @return void
     * @throws \Exception
     */
    private function translateAndPublishBatch(
        EventTranslatorInterface $translator,
        $batchStartsAt,
        $batchSize,
        $finalSequence,
        array $args = array()
    ) {
        $initialSequence = $finalSequence - ($batchSize - 1);
        try {
            $sequence = $initialSequence;
            $batchEndsAt = $batchStartsAt + $batchSize;
            for ($i = $batchStartsAt; $i < $batchEndsAt; $i++) {
                $translateArgs = array();
                foreach ($args as $arg) {
                    $translateArgs[] = $arg[$i];
                }
                $translator->translateTo($this->get($sequence), $sequence++, $translateArgs);
            }
        } catch (\Exception $e) {
            $this->sequencer->publish($initialSequence, $finalSequence);
            throw $e;
        }
        $this->sequencer->publish($initialSequence, $finalSequence);
    }
}
