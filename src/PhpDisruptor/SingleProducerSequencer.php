<?php

namespace PhpDisruptor;

use PhpDisruptor\Util\Util;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use Zend\Cache\Storage\StorageInterface;

/**
 * Coordinator for claiming sequences for access to a data structure while tracking dependent Sequences.
 *
 * Generally not safe for use from multiple threads as it does not implement any barriers.
 */
final class SingleProducerSequencer extends AbstractSequencer
{
    /**
     * @var int
     */
    public $nextValue;

    /**
     * @var int
     */
    public $cachedValue;

    /**
     * @inheritdoc
     */
    public function __construct($bufferSize, WaitStrategyInterface $waitStrategy)
    {
        parent::__construct($bufferSize, $waitStrategy);
        $this->nextValue = Sequence::INITIAL_VALUE;
        $this->cachedValue = Sequence::INITIAL_VALUE;
    }

    /**
     * @param int $requiredCapacity
     * @return bool
     */
    public function hasAvailableCapacity($requiredCapacity)
    {
        $nextValue = $this->nextValue;

        $wrapPoint = ($nextValue + $requiredCapacity) - $this->bufferSize;
        $cachedGatingSequence = $this->cachedValue;

        if ($wrapPoint > $cachedGatingSequence || $cachedGatingSequence > $nextValue) {
            $minSequence = Util::getMinimumSequence($this->getSequences(), $nextValue);
            $this->cachedValue = $minSequence;

            if ($wrapPoint > $minSequence) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $n
     * @return int
     * @throws Exception\InvalidArgumentException
     */
    public function next($n = 1)
    {
        if ($n < 1) {
            throw new Exception\InvalidArgumentException('$n must be > 0');
        }

        $nextValue = $this->nextValue;

        $nextSequence = $nextValue + $n;
        $wrapPoint = $nextSequence - $this->bufferSize;
        $cachedGatingSequence = $this->cachedValue;

        if ($wrapPoint > $cachedGatingSequence || $cachedGatingSequence >  $nextValue) {

            while ($wrapPoint > ($minSequence = Util::getMinimumSequence($this->getSequences(), $nextValue))) {
                $this->wait(1);
            }
            $this->cachedValue = $minSequence;
        }

        $this->nextValue = $nextSequence;

        return $nextSequence;
    }

    /**
     * @param int $n
     * @return int
     * @throws Exception\InvalidArgumentException
     * @throws Exception\InsufficientCapacityException
     */
    public function tryNext($n = 1)
    {
        if ($n < 1) {
            throw new Exception\InvalidArgumentException('$n must be > 0');
        }

        if (!$this->hasAvailableCapacity($n)) {
            throw new Exception\InsufficientCapacityException('insufficient capacity');
        }

        $nextSequence = $this->nextValue += $n;
        return $nextSequence;
    }

    /**
     * @return int
     */
    public function remainingCapacity()
    {
        $nextValue = $this->nextValue;

        $consumed = Util::getMinimumSequence($this->getSequences(), $nextValue);
        $produced = $nextValue;

        return $this->getBufferSize() - ($produced - $consumed);
    }

    /**
     * @param int
     * @return void
     */
    public function claim($sequence)
    {
        $this->nextValue = $sequence;
    }

    /**
     * @param int $low
     * @param int|null $high will get ignored in this implementation
     * @return void
     */
    public function publish($low, $high = null)
    {
        $this->cursor->set($low);
        $this->waitStrategy->signalAllWhenBlocking();
    }

    /**
     * @param int $sequence
     * @return bool
     */
    public function isAvailable($sequence)
    {
        return $sequence <= $this->cursor->get();
    }

    /**
     * @param int $lowerBound
     * @param int $availableSequence
     * @return int
     */
    public function getHighestPublishedSequence($lowerBound, $availableSequence)
    {
        return $availableSequence;
    }
}
