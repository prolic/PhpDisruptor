<?php

namespace PhpDisruptor;

use PhpDisruptor\Util\Util;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use Zend\Cache\Storage\StorageInterface;

abstract class AbstractSequencer implements SequencerInterface
{
    /**
     * @var int
     */
    protected $bufferSize;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var WaitStrategyInterface
     */
    protected $waitStrategy;

    /**
     * @var Sequence
     */
    protected $cursor;

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
            throw new Exception\InvalidArgumentException('$bufferSize must not be less than 1');
        }

        if (($bufferSize & ($bufferSize - 1)) == 0) {
            throw new Exception\InvalidArgumentException('$bufferSize must be a power of 2');
        }

        $this->storage = $storage;
        $this->bufferSize = $bufferSize;
        $this->waitStrategy = $waitStrategy;

        $keySuffix = sha1(gethostname() . getmypid() . microtime(true) . spl_object_hash($this));
        $this->key = 'sequencer_' . $keySuffix;
        $cursorKey = 'cursor_' . $keySuffix;

        $this->cursor = new Sequence($storage, SequencerInterface::INITIAL_CURSOR_VALUE, $cursorKey);

        $this->storage->setItem($this->key, array());
    }

    /**
     * Returns the gating sequences
     *
     * @return Sequence[]
     */
    public function getSequences()
    {
        $sequences = array();
        $content = $this->storage->getItem($this->key);
        foreach ($content as $sequence) {
            $sequences[] = new Sequence($this->storage, $sequence);
        }
        return $sequences;
    }

    /**
     * @param Sequence[] $sequences
     * @return bool
     */
    public function casSequences(array $sequences)
    {
        return Util::casSequences($this->storage, $this->key, $sequences);
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
}
