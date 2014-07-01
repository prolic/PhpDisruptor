<?php

namespace PhpDisruptor;

use PhpDisruptor\Lists\SequenceList;
use ConcurrentPhpUtils\CasThreadedMemberTrait;
use Threaded;
use PhpDisruptor\Util\Util;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;

abstract class AbstractSequencer extends Threaded implements SequencerInterface
{
    use CasThreadedMemberTrait;

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
     * @var SequenceList
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
        $this->sequences = new SequenceList();
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
     * @return SequenceList
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
     * @param SequenceList $gatingSequences
     * @return void
     */
    public function addGatingSequences(SequenceList $gatingSequences)
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
     * @param SequenceList|null $sequencesToTrack
     * @return ProcessingSequenceBarrier
     */
    public function newBarrier(SequenceList $sequencesToTrack = null)
    {
        if (null === $sequencesToTrack) {
            $sequencesToTrack = new SequenceList();
        }
        $barrier = new ProcessingSequenceBarrier($this, $this->waitStrategy, $this->cursor, $sequencesToTrack);
        return $barrier;
    }

    /**
     * @inheritdoc
     */
    public function casSequences(SequenceList $oldSequences, SequenceList $newSequences)
    {
        return Util::casSequences($this, $oldSequences, $newSequences);
    }

    /**
     * @param SequenceList $sequences
     * @return void
     */
    public function setSequences(SequenceList $sequences)
    {
        $this->sequences = $sequences;
    }
}
