<?php

namespace PhpDisruptor;

use Zend\Cache\Storage\StorageInterface;

abstract class SequenceGroups
{
    /**
     * @param SequenceHolderInterface $sequenceHolder
     * @param CursoredInterface $cursor
     * @param Sequence[] $sequencesToAdd
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public static function addSequences(
        SequenceHolderInterface $sequenceHolder,
        CursoredInterface $cursor,
        array $sequencesToAdd
    ) {

        do {
            $currentSequences = $sequenceHolder->getSequences();
            $updatedSequences = $currentSequences;
            $cursorSequence = $cursor->getCursor();

            foreach ($sequencesToAdd as $sequence) {
                if (!$sequence instanceof Sequence) {
                    throw new Exception\InvalidArgumentException(
                        '$sequences must be an array of PhpDisruptor\Sequence objects'
                    );
                }
                $sequence->set($cursorSequence);
                $updatedSequences[] = $sequence;
            }
        } while (!$sequenceHolder->casSequences($updatedSequences));

        $cursorSequence = $cursor->getCursor();
        foreach ($sequencesToAdd as $sequence) {
            $sequence->set($cursorSequence);
        }
    }

    /**
     * @param SequenceHolderInterface $sequenceHolder
     * @param Sequence $sequence
     * @return bool
     */
    public static function removeSequence(SequenceHolderInterface $sequenceHolder, Sequence $sequence)
    {
        do {
            $oldSequences = $sequenceHolder->getSequences();
            $numToRemove = self::countMatching($oldSequences, $sequence);
            if (0 == $numToRemove) {
                break;
            }

            $oldSize = count($oldSequences);

            $newSequences = array();
            for ($i = 0, $pos = 0; $i < $oldSize; $i++) {
                $testSequence = $oldSequences[$i];
                if (!$testSequence->equals($sequence)) {
                    $newSequences[$pos++] = $testSequence;
                }
            }
        } while (!$sequenceHolder->casSequences($newSequences));
        return $numToRemove != 0;
    }

    /**
     * @param Sequence[] $sequences
     * @param Sequence $sequence
     * @return int
     */
    private static function countMatching(array $sequences, Sequence $sequence)
    {
        $numToRemove = 0;
        foreach ($sequences as $sequenceToTest) {
            if ($sequenceToTest->equals($sequence)) {
                $numToRemove++;
            }
        }
        return $numToRemove;
    }
}
