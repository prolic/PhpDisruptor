<?php

namespace PhpDisruptor;

use PhpDisruptor\Util\Util;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use Stackable;

abstract class AbstractSequencer extends Stackable implements SequencerInterface
{
    /**
     * @var int
     */
    protected $bufferSize;

    /**
     * @var string
     */
    private $key;

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
    protected $sequences;

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

        if (($bufferSize & ($bufferSize - 1)) == 0) {
            throw new Exception\InvalidArgumentException('$bufferSize must be a power of 2');
        }

        $this->bufferSize = $bufferSize;
        $this->waitStrategy = $waitStrategy;

        $this->cursor = new Sequence();
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

    /**
     * @param Sequence[] $gatingSequences
     * @return void
     */
    public function addGatingSequences(array $gatingSequences)
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
     * @param array $sequencesToTrack
     * @return ProcessingSequenceBarrier
     */
    public function newBarrier(array $sequencesToTrack = array())
    {
        return new ProcessingSequenceBarrier($this, $this->waitStrategy, $this->cursor, $sequencesToTrack);
    }

    /**
     * @inheritdoc
     */
    public function casSequences(array $oldSequences, array $newSequences)
    {
        return Util::casSequences($this, $oldSequences, $newSequences);
    }

    /**
     * @param Sequence[] $sequences
     * @return void
     */
    public function setSequences(array $sequences)
    {
        $this->sequences = $sequences;
    }

    public function run()
    {}
}
