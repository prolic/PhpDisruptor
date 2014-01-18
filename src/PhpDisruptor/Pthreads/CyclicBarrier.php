<?php

namespace PhpDisruptor\Pthreads;

class CyclicBarrier extends StackableArray
{
    public $lock; // @todo
    public $trip; // @todo

    /**
     * @var int
     */
    public $parties;

    /**
     * @var \Thread
     */
    public $barrierCommand;

    /**
     * @var Generation
     */
    public $generation;

    /**
     * Number of parties still waiting. Counts down from parties to 0
     * on each generation.  It is reset to parties on each new
     * generation or when broken.
     *
     * @var int
     */
    public $count;

    /**
     * Updates state on barrier trip and wakes up everyone.
     * Called only while holding lock.
     */
    public function _nextGeneration() // public for pthreads reasons
    {
        // signal completion of last generation
        $this->trip.signalAll();
        // set up next generation
        $this->count = $this->parties;
        $this->generation = new Generation();
    }

    public function _breakBarrier() // public for pthreads reasons
    {
        $this->generation->setBroken();
        $this->count = $this->parties;
        $this->trip->signalAll();
    }


}
