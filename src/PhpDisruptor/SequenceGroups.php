<?php

namespace PhpDisruptor;

use Zend\Cache\Storage\StorageInterface;

abstract class SequenceGroups
{
    /**
     * @param SequencerInterface $sequencer
     * @param StorageInterface $storage
     * @param CursoredInterface $cursor
     * @param Sequence[] $sequencesToAdd
     */
    public static function addSequences(
        SequencerInterface $sequencer,
        // holder
        StorageInterface $storage,
        // updater
        CursoredInterface $cursor,
        $sequencesToAdd
    ) {

        $updatedSequences = array();
        $currentSequences = array();

        /*
        {
        long cursorSequence;
        Sequence[] updatedSequences;
        Sequence[] currentSequences;

        do
        {
            currentSequences = updater.get(holder);
            updatedSequences = copyOf(currentSequences, currentSequences.length + sequencesToAdd.length);
            cursorSequence = cursor.getCursor();

            int index = currentSequences.length;
            for (Sequence sequence : sequencesToAdd)
            {
                sequence.set(cursorSequence);
                updatedSequences[index++] = sequence;
            }
        }
        while (!updater.compareAndSet(holder, currentSequences, updatedSequences));

        cursorSequence = cursor.getCursor();
        for (Sequence sequence : sequencesToAdd)
        {
            sequence.set(cursorSequence);
        }

        */
    }
}
