<?php

namespace PhpDisruptor;

use Countable;
use PhpDisruptor\Util\Util;

final class SequenceGroup extends Sequence implements Countable, SequenceAggregateInterface
{
    /**
     * @var Sequence[]
     */
    public $sequences;

    public $hash;

    public function __construct()
    {
        $this->value = self::INITIAL_VALUE;
        $this->hash = spl_object_hash($this);
        $this->sequences = array();
    }

    /**
     * Get the sequences
     *
     * @return Sequence[]
     */
    public function getSequences()
    {
        return $this->sequences;
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
     * Set all Sequences in the group to a given value.
     *
     * @param int $value to set the group of sequences to.
     * @return void
     */
    public function set($value)
    {
        foreach ($this->sequences as $sequence) {
            $sequence->set($value);
        }
    }

    /**
     * Add a Sequence into this aggregate.  This should only be used during
     * initialisation.  Use {@link SequenceGroup#addWhileRunning(Cursored, Sequence)}
     *
     * @param Sequence $sequence to be added to the aggregate.
     * @return void
     */
    public function add(Sequence $sequence)
    {
        $this->lock();

        $oldSequences = $this->sequences;
        $newSequences = $oldSequences;
        $newSequences[] = $sequence;

        $this->sequences = $newSequences;

        $this->unlock();
    }

    /**
     * Remove the first occurrence of the Sequence from this aggregate.
     *
     * @param Sequence $sequence to be removed from this aggregate.
     * @return bool true if the sequence was removed otherwise false.
     */
    public function remove(Sequence $sequence)
    {
        return SequenceGroups::removeSequence($this, $sequence);
    }

    /**
     * Get the size of the group.
     *
     * @return int the size of the group.
     */
    public function count()
    {
        return count($this->sequences);
    }

    /**
     * Adds a sequence to the sequence group after threads have started to publish to
     * the Disruptor.  It will set the sequences to cursor value of the ringBuffer
     * just after adding them.  This should prevent any nasty rewind/wrapping effects.
     *
     * @param CursoredInterface $cursored The data structure that the owner of this sequence group will
     * be pulling it's events from.
     * @param Sequence[] $sequences The sequence to add.
     * @return void
     */
    public function addWhileRunning(CursoredInterface $cursored, array $sequences)
    {
        SequenceGroups::addSequences($this, $cursored, $sequences);
    }

    /**
     * @inheritdoc
     */
    public function casSequences(array $oldSequences, array $newSequences)
    {
        return Util::casSequences($this, $oldSequences, $newSequences);
    }

    /**
     * @param Sequence[] $sequences
     * @return void
     */
    public function setSequences(array $sequences)
    {
        $this->sequences = $sequences;
    }
}
