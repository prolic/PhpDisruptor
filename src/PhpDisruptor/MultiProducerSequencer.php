<?php

namespace PhpDisruptor;

use PhpDisruptor\Exception\InsufficientCapacityException;
use PhpDisruptor\Lists\SequenceList;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Util\Util;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;

final class MultiProducerSequencer extends AbstractSequencer
{
    /**
     * @var Sequence
     */
    public $gatingSequenceCache;

    /**
     * @var int[]
     */
    public $availableBuffer;

    /**
     * @var int
     */
    public $indexMask;

    /**
     * @var int
     */
    public $indexShift;

    /**
     * @inheritdoc
     */
    public function __construct($bufferSize, WaitStrategyInterface $waitStrategy)
    {
        parent::__construct($bufferSize, $waitStrategy);

        $this->gatingSequenceCache = new Sequence();

        $this->indexMask = $bufferSize - 1;
        $this->indexShift = Util::log2($bufferSize);

        $this->availableBuffer = new StackableArray();
        for ($i = 0; $i < $bufferSize; $i++) {
            $this->availableBuffer[$i] = -1;
        }
    }

    /**
     * @param int $requiredCapacity
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function hasAvailableCapacity($requiredCapacity)
    {
        return $this->_internalHasAvailableCapacity($this->getSequences(), $requiredCapacity, $this->cursor->get());
    }

    /**
     * @param SequenceList $gatingSequences
     * @param int $requiredCapacity
     * @param int $cursorValue
     * @return bool
     */
    public function _internalHasAvailableCapacity(SequenceList $gatingSequences, $requiredCapacity, $cursorValue) // private !! only public for pthreads reasons
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
                    $this->wait(1);
                    continue;
                }

                $this->gatingSequenceCache->set($gatingSequence);
            } elseif ($this->cursor->compareAndSwap($current, $next)) {
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

            if (!$this->_internalHasAvailableCapacity($this->getSequences(), $n, $current)) {
                throw new Exception\InsufficientCapacityException('insufficient capacity');
            }
        } while (!$this->cursor->compareAndSwap($current, $next));

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
            $this->_setAvailable($low);
        } else {
            for ($l = $low; $l <= $high; $l++) {
                $this->_setAvailable($l);
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
    public function _setAvailable($sequence) // private !! only public for pthreads reasons
    {
        $this->_setAvailableBufferValue($this->_calculateIndex($sequence), $this->_calculateAvailabilityFlag($sequence));
    }

    /**
     * @param int $index
     * @param int $flag
     * @return void
     */
    public function _setAvailableBufferValue($index, $flag) // private !! only public for pthreads reasons
    {
        do {
            $oldAvailableBuffer = $this->availableBuffer;
            $newAvailableBuffer = new StackableArray();
            $newAvailableBuffer->merge($oldAvailableBuffer);
            $newAvailableBuffer[$index] = $flag;
        } while (!$this->casMember('availableBuffer', $oldAvailableBuffer, $newAvailableBuffer));
    }

    /**
     * @inheritdoc
     */
    public function isAvailable($sequence)
    {
        $index = $this->_calculateIndex($sequence);
        $flag = $this->_calculateAvailabilityFlag($sequence);

        return $this->availableBuffer[$index] == $flag;
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
                return $sequence - 1 ;
            }
        }
        return $availableSequence;
    }

    /**
     * @param int $sequence
     * @return int
     */
    public function _calculateAvailabilityFlag($sequence) // private !! only public for pthreads reasons
    {
        return (int) ($sequence >> $this->indexShift);
    }

    /**
     * @param int $sequence
     * @return int
     */
    public function _calculateIndex($sequence) // private !! only public for pthreads reasons
    {
        return (int) ($sequence & $this->indexMask);
    }
}
