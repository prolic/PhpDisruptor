<?php

namespace PhpDisruptor;

use PhpDisruptor\Lists\SequenceList;

abstract class SequenceGroups
{
    /**
     * @param SequenceAggregateInterface $sequenceAggregate
     * @param CursoredInterface $cursor
     * @param SequenceList $sequencesToAdd
     * @return void
     */
    public static function addSequences(
        SequenceAggregateInterface $sequenceAggregate,
        CursoredInterface $cursor,
        SequenceList $sequencesToAdd
    ) {

        do {
            $currentSequences = $sequenceAggregate->getSequences();
            $updatedSequences = new SequenceList();
            $updatedSequences->merge($currentSequences);
            $cursorSequence = $cursor->getCursor();

            foreach ($sequencesToAdd as $sequence) {
                $sequence->set($cursorSequence);
                $updatedSequences[] = $sequence;
            }

        } while (!$sequenceAggregate->casSequences($currentSequences, $updatedSequences));

        $cursorSequence = $cursor->getCursor();
        foreach ($sequencesToAdd as $sequence) {
            $sequence->set($cursorSequence);
        }
    }

    /**
     * @param SequenceAggregateInterface $sequenceAggregate
     * @param Sequence $sequence
     * @return bool
     */
    public static function removeSequence(SequenceAggregateInterface $sequenceAggregate, Sequence $sequence)
    {
        do {
            $oldSequences = $sequenceAggregate->getSequences();
            $numToRemove = self::countMatching($oldSequences, $sequence);
            if (0 == $numToRemove) {
                break;
            }

            $oldSize = count($oldSequences);

            $newSequences = new SequenceList();
            for ($i = 0, $pos = 0; $i < $oldSize; $i++) {
                $testSequence = $oldSequences[$i];
                if (!$testSequence->equals($sequence)) {
                    $newSequences[$pos++] = $testSequence;
                }
            }
        } while (!$sequenceAggregate->casSequences($oldSequences, $newSequences));
        return $numToRemove != 0;
    }

    /**
     * @param SequenceList $sequences
     * @param Sequence $sequence
     * @return int
     */
    public static function countMatching(SequenceList $sequences, Sequence $sequence)
    {
        $numToRemove = 0;
        foreach ($sequences as $sequenceToTest) {
            if ($sequenceToTest == $sequence) {
                $numToRemove++;
            }
        }
        return $numToRemove;
    }
}
