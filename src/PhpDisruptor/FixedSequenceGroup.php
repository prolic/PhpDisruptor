<?php

namespace PhpDisruptor;

use ArrayIterator;
use CachingIterator;
use PhpDisruptor\Lists\SequenceList;
use ConcurrentPhpUtils\NoOpStackable;
use PhpDisruptor\Util\Util;

/**
 * Hides a group of Sequences behind a single Sequence
 */
final class FixedSequenceGroup extends Sequence
{
    /**
     * @var SequenceList
     */
    public $sequences;

    /**
     * Constructor
     *
     * @param SequenceList $sequences the list of sequences to be tracked under this sequence group
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(SequenceList $sequences)
    {
        $this->sequences = $sequences;
    }

    /**
     * Get the minimum sequence value for the group.
     *
     * @return int the minimum sequence value for the group.
     */
    public function get()
    {
        return Util::getMinimumSequence($this->sequences);
    }

    /**
     * Not supported.
     *
     * @throws Exception\UnsupportedMethodCallException
     */
    public function set($value)
    {
        throw new Exception\UnsupportedMethodCallException('not supported');
    }

    /**
     * Not supported.
     *
     * @throws Exception\UnsupportedMethodCallException
     */
    public function compareAndSwap($oldValue, $newValue)
    {
        throw new Exception\UnsupportedMethodCallException('not supported');
    }

    /**
     * Not supported.
     *
     * @throws Exception\UnsupportedMethodCallException
     */
    public function incrementAndGet()
    {
        throw new Exception\UnsupportedMethodCallException('not supported');
    }

    /**
     * Not supported.
     *
     * @throws Exception\UnsupportedMethodCallException
     */
    public function addAndGet($increment)
    {
        throw new Exception\UnsupportedMethodCallException('not supported');
    }
}
