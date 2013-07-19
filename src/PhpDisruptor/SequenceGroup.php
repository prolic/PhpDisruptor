<?php

namespace PhpDisruptor;

use Zend\Cache\Storage\StorageInterface;

class SequenceGroup extends Sequence
{
    /*
private static final AtomicReferenceFieldUpdater<SequenceGroup, Sequence[]> SEQUENCE_UPDATER =
AtomicReferenceFieldUpdater.newUpdater(SequenceGroup.class, Sequence[].class, "sequences");
private volatile Sequence[] sequences = new Sequence[0];
    */

    /**
     * @var Sequence[]
     */
    protected $sequences;

    public function __construct(StorageInterface $storage)
    {
        $this->sequences[] = new Sequence($storage);
        parent::__construct($storage, Sequence::INITIAL_VALUE);
    }

    /**
     * Get the minimum sequence value for the group.
     *
     * @return the minimum sequence value for the group.
     */
    @Override
    public long get()
{
        return Util.getMinimumSequence(sequences);
    }

    /**
     * Set all {@link Sequence}s in the group to a given value.
     *
     * @param value to set the group of sequences to.
     */
    @Override
    public void set(final long value)
    {
        final Sequence[] sequences = this.sequences;
        for (int i = 0, size = sequences.length; i < size; i++)
        {
            sequences[i].set(value);
        }
    }

    /**
     * Add a {@link Sequence} into this aggregate.  This should only be used during
     * initialisation.  Use {@link SequenceGroup#addWhileRunning(Cursored, Sequence)}
     *
     * @see SequenceGroup#addWhileRunning(Cursored, Sequence)
     * @param sequence to be added to the aggregate.
     */
    public void add(final Sequence sequence)
    {
        Sequence[] oldSequences;
        Sequence[] newSequences;
        do
        {
            oldSequences = sequences;
            final int oldSize = oldSequences.length;
            newSequences = new Sequence[oldSize + 1];
            System.arraycopy(oldSequences, 0, newSequences, 0, oldSize);
            newSequences[oldSize] = sequence;
        }
        while (!SEQUENCE_UPDATER.compareAndSet(this, oldSequences, newSequences));
    }

    /**
     * Remove the first occurrence of the {@link Sequence} from this aggregate.
     *
     * @param sequence to be removed from this aggregate.
     * @return true if the sequence was removed otherwise false.
     */
    public boolean remove(final Sequence sequence)
    {
        return SequenceGroups.removeSequence(this, SEQUENCE_UPDATER, sequence);
    }

    /**
     * Get the size of the group.
     *
     * @return the size of the group.
     */
    public int size()
{
        return sequences.length;
    }

    /**
     * Adds a sequence to the sequence group after threads have started to publish to
     * the Disruptor.  It will set the sequences to cursor value of the ringBuffer
     * just after adding them.  This should prevent any nasty rewind/wrapping effects.
     *
     * @param cursored The data structure that the owner of this sequence group will
     * be pulling it's events from.
     * @param sequence The sequence to add.
     */
    public void addWhileRunning(Cursored cursored, Sequence sequence)
    {
        SequenceGroups.addSequences(this, SEQUENCE_UPDATER, cursored, sequence);
    }
}
