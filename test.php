<?php

class Sequence extends Stackable
{
    protected $value = -1;

    public function run()
    {}

    public function get()
    {
        return $this->value;
    }

    public function set($value)
    {
        $this->value = $value;
    }
}

class SequenceGroup extends Sequence implements Countable
{
    /**
     * @var Sequence[]
     */
    protected $sequences;

    public function run()
    {
    }

    public function get()
    {
        $minimum = PHP_INT_MAX;

        foreach ($this->sequences as $sequence) {
            $value = $sequence->get();
            $minimum = min($minimum, $value);
        }

        return $minimum;
    }

    public function set($value)
    {
        foreach ($this->sequences as $sequence) {
            $sequence->set($value);
        }
    }

    public function add(Sequence $sequence)
    {
        $oldSequences = $this->sequences;
        $newSequences = $oldSequences;
        $newSequences[] = $sequence;
        $this->sequences = $newSequences;
    }

    public function count()
    {
        return count($this->sequences);
    }
}

$sequenceGroup = new SequenceGroup();
$sequenceGroup->add(new Sequence());
$sequenceGroup->add(new Sequence());

echo $sequenceGroup->count() . PHP_EOL;

$sequence1 = new Sequence();
$sequence2 = new Sequence();
$sequence3 = new Sequence();
$sequence4 = new Sequence();
$sequence5 = new Sequence();

$sequenceGroup->add($sequence1);
$sequenceGroup->add($sequence2);
$sequenceGroup->add($sequence3);
$sequenceGroup->add($sequence4);
$sequenceGroup->add($sequence5);

echo $sequenceGroup->count() . PHP_EOL;