<?php

namespace PhpDisruptor;

use PhpDisruptor\Pthreads\AtomicStackableTrait;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Util\Util;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use Stackable;

abstract class AbstractSequencer extends Stackable implements SequencerInterface
{
    use AtomicStackableTrait;

    /**
     * @var int
     */
    public $bufferSize;

    /**
     * @var WaitStrategyInterface
     */
    public $waitStrategy;

    /**
     * @var Sequence
     */
    public $cursor;

    /**
     * @var Sequence[]
     */
    public $sequences;

    /**
     * Construct a Sequencer with the selected wait strategy and buffer size.
     *
     * @param int $bufferSize
     * @param WaitStrategyInterface $waitStrategy
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($bufferSize, WaitStrategyInterface $waitStrategy)
    {
        if ($bufferSize < 1) {
            throw new Exception\InvalidArgumentException('$bufferSize must not be less than 1');
        }

        if ($this->bitCount($bufferSize) != 1) {
            throw new Exception\InvalidArgumentException('$bufferSize must be a power of 2');
        }

        $this->bufferSize = $bufferSize;
        $this->waitStrategy = $waitStrategy;
        $this->sequences = new StackableArray();
        $this->cursor = new Sequence();
    }

    /**
     * @param int $n
     * @return int
     */
    public function bitCount($n)
    {
        $count = 0;
        while ($n != 0) {
            $count++;
            $n &= ($n - 1);
        }
        return $count;
    }

    /**
     * Returns the gating sequences
     *
     * @return Sequence[]
     */
    public function getSequences()
    {
        return $this->sequences;
    }

    /**
     * Get cursor
     *
     * @return int
     */
    public function getCursor()
    {
        return $this->cursor->get();
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

    /**
     * @param Sequence[] $gatingSequences
     * @return void
     */
    public function addGatingSequences(StackableArray $gatingSequences)
    {
        SequenceGroups::addSequences($this, $this, $gatingSequences);
    }

    /**
     * @param Sequence $sequence
     * @return bool|void
     */
    public function removeGatingSequence(Sequence $sequence)
    {
        return SequenceGroups::removeSequence($this, $sequence);
    }

    /**
     * @inheritdoc
     */
    public function getMinimumSequence()
    {
        return Util::getMinimumSequence($this->getSequences(), $this->cursor->get());
    }

    /**
     * @param StackableArray $sequencesToTrack
     * @return ProcessingSequenceBarrier
     */
    public function newBarrier(StackableArray $sequencesToTrack = null)
    {
        if (null === $sequencesToTrack) {
            $sequencesToTrack = new StackableArray();
        }
        return new ProcessingSequenceBarrier($this, $this->waitStrategy, $this->cursor, $sequencesToTrack);
    }

    /**
     * @inheritdoc
     */
    public function casSequences(StackableArray $oldSequences, StackableArray $newSequences)
    {
        return Util::casSequences($this, $oldSequences, $newSequences);
    }

    /**
     * @param Sequence[] $sequences
     * @return void
     */
    public function setSequences(StackableArray $sequences)
    {
        $this->sequences = $sequences;
    }
}
