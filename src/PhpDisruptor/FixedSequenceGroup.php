<?php

namespace PhpDisruptor;

use ArrayIterator;
use CachingIterator;
use PhpDisruptor\Util\Util;

/**
 * Hides a group of Sequences behind a single Sequence
 */
final class FixedSequenceGroup extends Sequence
{
    /**
     * @var Sequence[]
     */
    protected $sequences;

    /**
     * Constructor
     *
     * @param Sequence[] $sequences the list of sequences to be tracked under this sequence group
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $sequences)
    {
        foreach ($sequences as $sequence) {
            if (!$sequence instanceof Sequence) {
                throw new Exception\InvalidArgumentException(
                    'sequence must be an instance of PhpDisruptor\Sequence'
                );
            }
        }
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
     * @return string
     */
    public function __toString()
    {
        $it = new CachingIterator(
            new ArrayIterator($this->sequences),
            CachingIterator::FULL_CACHE
        );

        $output = '[';
        foreach ($it as $sequence) {
            $output .= (string) $sequence;
            if ($it->hasNext()) {
                $output .= ', ';
            }
        }
        $output .= ']';

        return $output;
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
