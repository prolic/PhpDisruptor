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
        echo 'next generation' . PHP_EOL;
        // signal completion of last generation
        Cond::signal($this->trip);
        // set up next generation
        $this->count = $this->parties;
        $this->generation = new Generation();
        echo 'end ng' . PHP_EOL;
    }

    public function breakBarrier()
    {
        echo 'break barrier' . PHP_EOL;
        $this->generation->setBroken();
        $this->count = $this->parties;
        var_dump(Cond::signal($this->trip));
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
        echo __LINE__ . 'Thread . locked' . "\n";
        try {

            echo 'broken ? ' . ($this->generation->broken() ? 'yes' : 'no') . PHP_EOL;
            if ($this->generation->broken()) {
                throw new Exception\BrokenBarrierException();
            }

            $index = --$this->count;
            echo 'index: ' . $index . PHP_EOL;
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

                if ($this->generation->broken()) {
                    throw new Exception\BrokenBarrierException();
                }

                if ($timed && $micros > 0) {
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
        echo 'await ?' . PHP_EOL;
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
