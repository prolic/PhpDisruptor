<?php

namespace PhpDisruptor;

interface SequencerInterface extends CursoredInterface, SequenceHolderInterface
{
    const INITIAL_CURSOR_VALUE = -1;

    /**
     * The capacity of the data structure to hold entries.
     *
     * @return int the size of the RingBuffer.
     */
    public function getBufferSize();

    /**
     * Has the buffer got capacity to allocate another sequence.  This is a concurrent
     * method so the response should only be taken as an indication of available capacity.
     *
     * @param int $requiredCapacity in the buffer
     * @return bool true, if the buffer has the capacity to allocate the next sequence otherwise false.
     */
    public function hasAvailableCapacity($requiredCapacity);

    /**
     * Claim the next event in sequence for publishing.
     *
     * @param int $n
     * @return int the claimed sequence value
     */
    public function next($n = 1);

    /**
     * Attempt to claim the next event in sequence for publishing.  Will return the
     * number of the slot if there is at least <code>requiredCapacity</code> slots
     * available.
     *
     * @param int|null $n
     * @return int the claimed sequence value
     * @throws Exception\InsufficientCapacityException
     */
    public function tryNext($n = null);

    /**
     * Get the remaining capacity for this sequencer.
     *
     * @return int The number of slots remaining.
     */
    public function remainingCapacity();

    /**
     * Claim a specific sequence.  Only used if initialising the ring buffer to
     * a specific value.
     *
     * @param int $sequence The sequence to initialise too.
     * @return void
     */
    public function claim($sequence);

    /**
     * Publishes a sequence. Call when the event has been filled.
     *
     * @param int $low first sequence number to publish
     * @param int|null $high last sequence number to publish (optional)
     * @return void
     */
    public function publish($low, $high = null);

    /**
     * Confirms if a sequence is published and the event is available for use; non-blocking.
     *
     * @param int $sequence of the buffer to check
     * @return bool true if the sequence is available for use, false if not
     */
    public function isAvailable($sequence);

    /**
     * Add the specified gating sequences to this instance of the Disruptor.  They will
     * safely and atomically added to the list of gating sequences.
     *
     * @param Sequence[] $gatingSequences The sequences to add.
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function addGatingSequences(array $gatingSequences);

    /**
     * Remove the specified sequence from this sequencer.
     *
     * @param Sequence $sequence to be removed.
     * @return bool true if this sequence was found, false otherwise.
     */
    public function removeGatingSequence(Sequence $sequence);

    /**
     * Create a new SequenceBarrier to be used by an EventProcessor to track which messages
     * are available to be read from the ring buffer given a list of sequences to track.
     *
     * @see SequenceBarrierInterface
     * @param Sequence[] $sequencesToTrack
     * @return SequenceBarrierInterface A sequence barrier that will track the specified sequences.
     */
    public function newBarrier(array $sequencesToTrack = array());

    /**
     * Get the minimum sequence value from all of the gating sequences
     * added to this ringBuffer.
     *
     * @return int The minimum gating sequence or the cursor sequence if
     * no sequences have been added.
     */
    public function getMinimumSequence();

    /**
     * Get the highest sequence value from all the gatting sequences
     * added to this ringBuffer.
     *
     * @param int $sequence
     * @param int $availableSequence
     * @return int The highest gating sequence or the cursor sequence if
     * no sequences have been added.
     */
    public function getHighestPublishedSequence($sequence, $availableSequence);
}
