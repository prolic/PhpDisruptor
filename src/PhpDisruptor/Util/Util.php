<?php

namespace PhpDisruptor\Util;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceAggregateInterface;
use Zend\Cache\Storage\StorageInterface;

final class Util
{
    /**
     * Calculate the next power of 2, greater than or equal to x.<p>
     * From Hacker's Delight, Chapter 3, Harry S. Warren Jr.
     *
     * @param int $x Value to round up
     * @return int The next power of 2 from x inclusive
     * @throws Exception\InvalidArgumentException
     */
    public static function ceilingNextPowerOfTwo($x)
    {
        if (!is_numeric($x)) {
            throw new Exception\InvalidArgumentException('$x must be an integer');
        }

        $size = PHP_INT_SIZE * 8;
        $binary = str_pad(decbin($x -1), $size, 0, STR_PAD_LEFT);
        $numberOfLeadingZeros = strpos($binary, '1');

        return 1 << ($size - $numberOfLeadingZeros);
    }

    /**
     * Get the minimum sequence from an array of {@link com.lmax.disruptor.Sequence}s.
     *
     * @param Sequence[] $sequences to compare.
     * @param int|null  $minimum
     * @return int the minimum sequence found or PHP_INT_MAX if the array is empty
     * @throws Exception\InvalidArgumentException
     */
    public static function getMinimumSequence(array $sequences, $minimum = null)
    {
        if (null === $minimum) {
            $minimum = PHP_INT_MAX;
        }
        if (!is_numeric($minimum)) {
            throw new Exception\InvalidArgumentException('$minimum must be an integer or null');
        }

        foreach ($sequences as $sequence) {
            if (!$sequence instanceof Sequence) {
                throw new Exception\InvalidArgumentException('$sequence must be an instance of PhpDisruptor\Sequence');
            }
            $value = $sequence->get();
            $minimum = min($minimum, $value);
        }

        return $minimum;
    }

    /**
     * Get an array of Sequences for the passed EventProcessors
     *
     * @param AbstractEventProcessor[] $processors for which to get the sequences
     * @return Sequence[] the array of Sequences
     * @throws Exception\InvalidArgumentException
     */
    public static function getSequencesFor(array $processors)
    {
        $sequences = array();
        foreach ($processors as $eventProcessor) {
            if (!$eventProcessor instanceof AbstractEventProcessor) {
                throw new Exception\InvalidArgumentException(
                    '$processor must be an instance of PhpDisruptor\AbstractEventProcessor'
                );
            }
            $sequences[] = $eventProcessor->getSequence();
        }
        return $sequences;
    }

    /**
     * @param SequenceAggregateInterface $sequenceAggregate
     * @param Sequence[] $oldSequences
     * @param Sequence[] $newSequences
     * @return bool
     */
    public static function casSequences(
        SequenceAggregateInterface $sequenceAggregate,
        array $oldSequences,
        array $newSequences
    ) {
        $set = false;
        $sequenceAggregate->lock();
        if ($sequenceAggregate->getSequences() == $oldSequences) {
            $sequenceAggregate->setSequences($newSequences);
            $set = true;
        }
        $sequenceAggregate->unlock();
        return $set;
    }

    /**
     * Calculate the log base 2 of the supplied integer, essentially reports the location
     * of the highest bit.
     *
     * @param int $i Value to calculate log2 for.
     * @return int The log2 value
     * @throws Exception\InvalidArgumentException
     */
    public static function log2($i)
    {
        if (!is_numeric($i)) {
            throw new Exception\InvalidArgumentException('$i must be an integer');
        }
        $r = 0;
        while (($i >>= 1) != 0) {
            ++$r;
        }
        return $r;
    }
}
