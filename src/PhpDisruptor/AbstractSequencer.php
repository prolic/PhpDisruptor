<?php

namespace PhpDisruptor;

use Zend\Cache\Storage\StorageInterface;

abstract class AbstractSequencer implements SequencerInterface
{

    /*
    private static final AtomicReferenceFieldUpdater<AbstractSequencer, Sequence[]> SEQUENCE_UPDATER =
    AtomicReferenceFieldUpdater.newUpdater(AbstractSequencer.class, Sequence[].class, "gatingSequences");
    */

    /**
     * @var int
     */
    protected $bufferSize;

    /**
     * @var WaitStrategyInterface
     */
    protected $waitStrategy;

    /**
     * @var Sequence
     */
    protected $cursor;

    /**
     * @var Sequence[]
     */
    protected $gatingSequences;

    /*
    protected final int bufferSize;
    protected final WaitStrategy waitStrategy;
    protected final Sequence cursor = new Sequence(Sequencer.INITIAL_CURSOR_VALUE);
    protected volatile Sequence[] gatingSequences = new Sequence[0];
    */
    /**
     * @param StorageInterface $storage
     * @param int $bufferSize
     * @param WaitStrategyInterface $waitStrategy
     */
    public function __construct(StorageInterface $storage, $bufferSize, WaitStrategyInterface $waitStrategy)
    {
        if ($bufferSize < 1) {
            throw new Exception\InvalidArgumentException('buffer size must not be less than 1');
        }

        if (($bufferSize & ($bufferSize - 1)) == 0) {
            throw new Exception\InvalidArgumentException('buffer size must be a power of 2');
        }

        $this->cursor = new Sequence($storage);
        $this->gatingSequences[] = new Sequence($storage);
        $this->waitStrategy = $waitStrategy;
    }

    /**
     * Get cursor
     *
     * @return int
     */
    public function getCursor()
    {
        $this->cursor->get();
    }

    /**
     * The capacity of the data structure to hold entries.
     *
     * @return int the size of the RingBuffer.
     */
    public function getBufferSize()
    {
        return $this->bufferSize;
    }

    /*
    public final void addGatingSequences(Sequence... gatingSequences)
    {
    SequenceGroups.addSequences(this, SEQUENCE_UPDATER, this, gatingSequences);
    }
    */

    /**
     * @see Sequencer#removeGatingSequence(Sequence)
     */

    /*
    public boolean removeGatingSequence(Sequence sequence)
    {
        return SequenceGroups.removeSequence(this, SEQUENCE_UPDATER, sequence);
    }
    */

    /**
     * @see Sequencer#getMinimumSequence()
     */
    /*
    public long getMinimumSequence()
    {
        return Util.getMinimumSequence(gatingSequences, cursor.get());
    }
    */

    /**
     * @see Sequencer#newBarrier(Sequence...)
     */
    /*
    public SequenceBarrier newBarrier(Sequence... sequencesToTrack)
    {
        return new ProcessingSequenceBarrier(this, waitStrategy, cursor, sequencesToTrack);
    }
    */
}
