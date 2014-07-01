<?php

namespace PhpDisruptor\Util;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Exception;
use PhpDisruptor\Lists\EventProcessorList;
use PhpDisruptor\Lists\SequenceList;
use Threaded;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceAggregateInterface;

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
        $size = PHP_INT_SIZE * 8;
        $binary = str_pad(decbin($x -1), $size, 0, STR_PAD_LEFT);
        $numberOfLeadingZeros = strpos($binary, '1');

        return 1 << ($size - $numberOfLeadingZeros);
    }

    /**
     * Get the minimum sequence from an SequenceList
     *
     * @param SequenceList $sequences to compare
     * @param int|null  $minimum
     * @return int the minimum sequence found or PHP_INT_MAX if the sequence list is empty
     * @throws Exception\InvalidArgumentException
     */
    public static function getMinimumSequence(SequenceList $sequences, $minimum = PHP_INT_MAX)
    {
        foreach ($sequences as $sequence) {
            $value = $sequence->get();
            $minimum = min($minimum, $value);
        }
        return $minimum;
    }

    /**
     * Get a SequenceList of Sequences for the passed EventProcessors
     *
     * @param EventProcessorList $processors for which to get the sequences
     * @return SequenceList of Sequences
     */
    public static function getSequencesFor(EventProcessorList $processors)
    {
        $sequences = new SequenceList();
        foreach ($processors as $eventProcessor) {
            $sequences->add($eventProcessor->getSequence());
        }
        return $sequences;
    }

    /**
     * @param SequenceAggregateInterface $sequenceAggregate
     * @param SequenceList $oldSequences
     * @param SequenceList $newSequences
     * @return bool
     */
    public static function casSequences(
        SequenceAggregateInterface $sequenceAggregate,
        SequenceList $oldSequences,
        SequenceList $newSequences
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
        $r = 0;
        while (($i >>= 1) != 0) {
            ++$r;
        }
        return $r;
    }
}
