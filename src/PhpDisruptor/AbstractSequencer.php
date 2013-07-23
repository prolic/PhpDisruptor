<?php

namespace PhpDisruptor;

use PhpDisruptor\Util\Util;
use Zend\Cache\Storage\StorageInterface;

abstract class AbstractSequencer implements SequencerInterface
{
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

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Construct a Sequencer with the selected wait strategy and buffer size.
     *
     * @param StorageInterface $storage
     * @param int $bufferSize
     * @param WaitStrategyInterface $waitStrategy
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(StorageInterface $storage, $bufferSize, WaitStrategyInterface $waitStrategy)
    {
        if ($bufferSize < 1) {
            throw new Exception\InvalidArgumentException('buffer size must not be less than 1');
        }

        if (($bufferSize & ($bufferSize - 1)) == 0) {
            throw new Exception\InvalidArgumentException('buffer size must be a power of 2');
        }

        $this->storage = $storage;
        $this->bufferSize = $bufferSize;
        $this->waitStrategy = $waitStrategy;
        $this->cursor = new Sequence($storage, SequencerInterface::INITIAL_CURSOR_VALUE);
        $this->gatingSequences = array();
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
     * @inheritdoc
     */
    public function getMinimumSequence()
    {
        return Util::getMinimumSequence($this->gatingSequences, $this->cursor->get());
    }

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
