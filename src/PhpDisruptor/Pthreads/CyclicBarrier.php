<?php

namespace PhpDisruptor\Pthreads;

use Cond;
use Mutex;
use PhpDisruptor\Exception\TimeoutException;
use Thread;

class CyclicBarrier extends StackableArray
{
    /**
     * @var int the lock for guarding the barrier entry
     */
    public $lock;

    /**
     * @var int condition to wait until tripped
     */
    public $trip;

    /**
     * @var int the number of parties
     */
    public $parties;

    /**
     * @var Thread the command to run when tripped
     */
    public $barrierCommand;

    /**
     * @var Generation the current generation
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

    public function __construct($parties, Thread $barrierAction = null)
    {
        if ($parties <= 0) {
            throw new Exception\InvalidArgumentException();
        }
        $this->parties = $parties;
        $this->barrierCommand = $barrierAction;
        $this->lock = Mutex::create(false);
        $this->trip = Cond::create();
        $this->generation = new Generation();
    }

    /**
     * Updates state on barrier trip and wakes up everyone.
     * Called only while holding lock.
     */
    private function nextGeneration()
    {
        // signal completion of last generation
        $this->trip->signal();
        // set up next generation
        $this->count = $this->parties;
        $this->generation = new Generation();
    }

    private function breakBarrier()
    {
        $this->generation->setBroken();
        $this->count = $this->parties;
        $this->trip->signal();
    }

    private function doWait($timed, $nanos)
    {
        Mutex::lock($this->lock);
        try {

            if ($this->generation->broken()) {
                throw new Exception\BrokenBarrierException();
            }

            $index = --$this->count;
            if ($index == 0) { // tripped
                $ranAction = false;
                try {
                    if (null !== $this->barrierCommand) {
                        $this->barrierCommand->start();
                        $ranAction = true;
                        $this->nextGeneration();
                        return 0;
                    }
                } catch (\Exception $e) {
                    if (!$ranAction) {
                        $this->breakBarrier();
                    }
                }
            }

            // loop until tripped, broken or timed out
            for (;;) {

                if (!$timed) {
                    Cond::wait($this->trip, $this->lock);
                } else if ($nanos > 0) {
                    $micros = TimeUnit::NANOSECONDS()->toMicros($nanos);
                    Cond::wait($this->trip, $this->lock, $micros);
                }

                if ($this->generation->broken()) {
                    throw new Exception\BrokenBarrierException();
                }

                if ($timed && $nanos > 0) {
                    $this->breakBarrier();
                    throw new Exception\TimeoutException();
                }
            }
        } catch (\Exception $e) {
            Mutex::unlock($this->lock);
            throw $e;
        }
        Mutex::unlock($this->lock);
    }

    public function getParties()
    {
        return $this->parties;
    }

    public function await($timeout = 0, TimeUnit $unit = null)
    {
        if (null !== $unit) {
            return $this->doWait(false, 0);
        } else {
            $micros = $unit->toMicros($timeout);
            return $this->doWait(true, $micros);
        }
    }

    public function isBroken()
    {
        Mutex::lock($this->lock);
        try {
            $res = $this->generation->broken();
        } catch (\Exception $e) {
            Mutex::unlock($this->lock);
            throw $e;
        }
        Mutex::unlock($this->lock);
        return $res;
    }

    public function reset()
    {
        Mutex::lock($this->lock);
        try {
            $this->breakBarrier();
            $this->nextGeneration();
        } catch (\Exception $e) {
            Mutex::unlock($this->lock);
            throw $e;
        }
        Mutex::unlock($this->lock);
    }

    public function getNumberWaiting()
    {
        Mutex::lock($this->lock);
        try {
            $res = $this->parties - $this->count;
        } catch (\Exception $e) {
            Mutex::unlock($this->lock);
            throw $e;
        }
        Mutex::unlock($this->lock);
        return $res;
    }
}
