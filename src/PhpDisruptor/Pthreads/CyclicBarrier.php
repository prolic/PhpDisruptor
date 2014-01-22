<?php

namespace PhpDisruptor\Pthreads;

use Cond;
use Mutex;
use Thread;

require_once __DIR__ . '/Exception/TimeoutException.php';
require_once __DIR__ . '/Exception/BrokenBarrierException.php';

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

    /**
     * Constructor
     *
     * @param int $parties
     * @param Thread $barrierAction
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($parties, Thread $barrierAction = null)
    {
        if ($parties <= 0) {
            throw new Exception\InvalidArgumentException();
        }
        $this->parties = $parties;
        $this->count = $parties;
        $this->barrierCommand = $barrierAction;
        $this->lock = Mutex::create(false);
        $this->trip = Cond::create();
        $this->generation = new Generation();
    }

    /**
     * Updates state on barrier trip and wakes up everyone.
     * Called only while holding lock.
     *
     * @return void
     */
    public function nextGeneration()
    {
        // signal completion of last generation
        Cond::broadcast($this->trip);
        // set up next generation
        $this->count = $this->parties;
        $this->generation = new Generation();
    }

    public function breakBarrier()
    {
        $this->generation->setBroken();
        $this->count = $this->parties;
        Cond::broadcast($this->trip);
    }

    /**
     * @param bool $timed
     * @param int $micros
     * @return int
     * @throws \Exception
     */
    public function doWait($timed, $micros)
    {
        Mutex::lock($this->lock);
        try {

            $generation = $this->generation;
            if ($generation->broken()) {
                throw new Exception\BrokenBarrierException();
            }

            $index = --$this->count;
            if ($index == 0) { // tripped
                $ranAction = false;
                try {
                    if (null !== $this->barrierCommand) {
                        $this->barrierCommand->start();
                    }
                    $ranAction = true;
                    $this->nextGeneration();
                    Mutex::unlock($this->lock);
                    return 0;
                } catch (\Exception $e) {
                    if (!$ranAction) {
                        $this->breakBarrier();
                    }
                    throw $e;
                }
            }

            // loop until tripped, broken or timed out
            for (;;) {
                if (!$timed) {
                    Cond::wait($this->trip, $this->lock);
                } else if ($micros > 0) {
                    Cond::wait($this->trip, $this->lock, $micros);
                }

                if ($generation->broken()) {
                    throw new Exception\BrokenBarrierException();
                }

                if ($timed) {
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

    /**
     * @param int|null $timeout
     * @return int
     * @throws Exception\InvalidArgumentException if timeout is given without a timeunit
     */
    public function await($timeout = null)
    {
        if (null === $timeout) {
            return $this->doWait(false, 0);
        }
        return $this->doWait(true, $timeout);
    }

    /**
     * @return bool
     * @throws \Exception
     */
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

    public function __destruct()
    {
        Mutex::destroy($this->lock);
        Cond::destroy($this->trip);
    }
}
