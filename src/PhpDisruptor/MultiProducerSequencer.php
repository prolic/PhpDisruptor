<?php

namespace PhpDisruptor;

use PhpDisruptor\Exception\InsufficientCapacityException;
use PhpDisruptor\Util\Util;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;

final class MultiProducerSequencer extends AbstractSequencer
{
    /**
     * @var Sequence
     */
    private $gatingSequenceCache;

    /**
     * @var array
     */
    private $availableBuffer;

    /**
     * @var int
     */
    private $indexMask;

    /**
     * @var int
     */
    private $indexShift;

    /**
     * @inheritdoc
     */
    public function __construct($bufferSize, WaitStrategyInterface $waitStrategy)
    {
        parent::__construct($bufferSize, $waitStrategy);

        $this->gatingSequenceCache = new Sequence();

        $this->indexMask = $bufferSize - 1;
        $this->indexShift = Util::log2($bufferSize);
        $this->initAvailableBuffer();
    }

    /**
     * @return void
     */
    private function initAvailableBuffer()
    {
        $buffer = array();
        for ($i = 0; $i < $this->bufferSize; $i++) {
            $buffer[$i] = -1;
        }
        $this->availableBuffer = $buffer;
    }

    /**
     * @param int $requiredCapacity
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function hasAvailableCapacity($requiredCapacity)
    {
        if (!is_numeric($requiredCapacity)) {
            throw new Exception\InvalidArgumentException('$requiredCapacity must be an integer');
        }
        return $this->internalHasAvailableCapacity($this->getSequences(), $requiredCapacity, $this->cursor->get());
    }

    /**
     * @param Sequence[] $gatingSequences
     * @param int $requiredCapacity
     * @param int $cursorValue
     * @return bool
     */
    private function internalHasAvailableCapacity(array $gatingSequences, $requiredCapacity, $cursorValue)
    {
        $wrapPoint = ($cursorValue + $requiredCapacity) - $this->bufferSize;
        $cachedGatingSequence = $this->gatingSequenceCache->get();

        if ($wrapPoint > $cachedGatingSequence || $cachedGatingSequence > $cursorValue) {
            $minSequence = Util::getMinimumSequence($gatingSequences, $cursorValue);
            $this->gatingSequenceCache->set($minSequence);

            if ($wrapPoint > $minSequence) {
                return false;
            }
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function claim($sequence)
    {
        $this->cursor->set($sequence);
    }

    /**
     * @inheritdoc
     */
    public function next($n = 1)
    {
        if ($n < 1) {
            throw new Exception\InvalidArgumentException('$n must be > 0');
        }

        do {
            $current = $this->cursor->get();
            $next = $current + $n;

            $wrapPoint = $next - $this->bufferSize;
            $cachedGatingSequence = $this->gatingSequenceCache->get();

            if ($wrapPoint > $cachedGatingSequence || $cachedGatingSequence > $current) {
                $gatingSequence = Util::getMinimumSequence($this->getSequences(), $current);

                if ($wrapPoint > $gatingSequence) {
                    time_nanosleep(0, 1); // @todo: should we spin based on the wait strategy?
                    continue;
                }

                $this->gatingSequenceCache->set($gatingSequence);
            } elseif ($this->cursor->compareAndSet($current, $next)) {
                break;
            }
        } while (true);

        return $next;
    }

    /**
     * @inheritdoc
     */
    public function tryNext($n = 1)
    {
        if ($n < 1) {
            throw new Exception\InvalidArgumentException('$n must be > 0');
        }

        do {
            $current = $this->cursor->get();
            $next = $current + $n;

            if (!$this->hasAvailableCapacity($this->getSequences(), $n, $current)) {
                throw new Exception\InsufficientCapacityException('insufficient capacity');
            }
        } while (!$this->cursor->compareAndSet($current, $next));

        return $next;
    }

    /**
     * @inheritdoc
     */
    public function remainingCapacity()
    {
        $consumed = Util::getMinimumSequence($this->getSequences(), $this->cursor->get());
        $produced = $this->cursor->get();
        return $this->getBufferSize() - ($produced - $consumed);
    }

    /**
     * @inheritdoc
     */
    public function publish($low, $high = null)
    {
        if (null === $high) {
            $this->setAvailable($low);
        } else {
            for ($l = $low; $l <= $high; $l++) {
                $this->setAvailable($l);
            }
        }
        $this->waitStrategy->signalAllWhenBlocking();
    }

    /**
     * The below methods work on the availableBuffer flag.
     *
     * The prime reason is to avoid a shared sequence object between publisher threads.
     * (Keeping single pointers tracking start and end would require coordination
     * between the threads).
     *
     * --  Firstly we have the constraint that the delta between the cursor and minimum
     * gating sequence will never be larger than the buffer size (the code in
     * next/tryNext in the Sequence takes care of that).
     * -- Given that; take the sequence value and mask off the lower portion of the
     * sequence as the index into the buffer (indexMask). (aka modulo operator)
     * -- The upper portion of the sequence becomes the value to check for availability.
     * ie: it tells us how many times around the ring buffer we've been (aka division)
     * -- Because we can't wrap without the gating sequences moving forward (i.e. the
     * minimum gating sequence is effectively our last available position in the
     * buffer), when we have new data and successfully claimed a slot we can simply
     * write over the top.
     *
     * @param int $sequence
     * @return void
     */
    private function setAvailable($sequence)
    {
        $this->setAvailableBufferValue($this->calculateIndex($sequence), $this->calculateAvailabilityFlag($sequence));
    }

    /**
     * @param int $index
     * @param int $flag
     * @return void
     */
    private function setAvailableBufferValue($index, $flag)
    {
        do {
            $oldValue = $this->storage->getItem($this->availableBufferKeys[$index]);
        } while (!$this->storage->checkAndSetItem($oldValue, $this->availableBufferKeys[$index], $flag));
    }

    /**
     * @inheritdoc
     */
    public function isAvailable($sequence)
    {
        if (!is_numeric($sequence)) {
            throw new Exception\InvalidArgumentException('$sequence must be an integer');
        }
        $index = $this->calculateIndex($sequence);
        $flag = $this->calculateAvailabilityFlag($sequence);

        return $this->storage->getItem($this->availableBufferKeys[$index]) == $flag;
    }

    /**
     * @param int $lowerBound
     * @param int $availableSequence
     * @return int
     */
    public function getHighestPublishedSequence($lowerBound, $availableSequence)
    {
        for ($sequence = $lowerBound; $sequence <= $availableSequence; $sequence++) {
            if (!$this->isAvailable($sequence)) {
                return $sequence -1 ;
            }
        }
        return $availableSequence;
    }

    /**
     * @param int $sequence
     * @return int
     */
    private function calculateAvailabilityFlag($sequence)
    {
        return (int) ($sequence >> $this->indexShift);
    }

    /**
     * @param int $sequence
     * @return int
     */
    private function calculateIndex($sequence)
    {
        return (int) ($sequence & $this->indexMask);
    }
}
