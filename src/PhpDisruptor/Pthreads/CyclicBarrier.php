<?php

namespace PhpDisruptor\Pthreads;

use Cond;
use Mutex;
use Thread;

require_once __DIR__ . '/Exception/BrokenBarrierException.php';
require_once __DIR__ . '/Exception/InterruptedException.php';
require_once __DIR__ . '/Exception/InvalidArgumentException.php';
require_once __DIR__ . '/Exception/TimeoutException.php';

class CyclicBarrier extends StackableArray
{
    /**
     * @var int the lock for guarding the barrier entry
     */
    public $mutex;

    /**
     * @var int condition to wait until tripped
     */
    public $cond;

    /**
     * Constructor
     *
     * @param int $parties
     * @param Thread|null $barrierAction
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
        $this->mutex = Mutex::create(false);
        $this->cond = Cond::create();
        $this->generation = new Generation();
    }

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
     * Updates state on barrier trip and wakes up everyone.
     * Called only while holding lock.
     *
     * @return void
     * @visibility private
     */
    public function nextGeneration()
    {
        // signal completion of last generation
        Cond::broadcast($this->cond);

        // set up next generation
        $this->count = $this->parties;
        $this->generation = new Generation();
    }

    /**
     * Sets current barrier generation as broken and wakes up everyone.
     * Called only while holding lock.
     *
     * @return void
     * @visibility private
     */
    public function breakBarrier()
    {
        $g = $this->generation;
        $b = $g->broken = true;
        $this->count = $this->parties;
        Cond::broadcast($this->cond);
    }

    /**
     * Returns the number of parties
     *
     * @return int
     */
    public function getParties()
    {
        return $this->parties;
    }

    /**
     * @param int|null $timeout (optional) timeout in microseconds
     * @return int
     * @throws Exception\InvalidArgumentException
     * @throws Exception\BrokenBarrierException
     * @throws Exception\TimeoutException
     * @throws \Exception
     */
    public function await($timeout = null)
    {
        $m = $this->mutex;
        Mutex::lock($m);

        $that = $this;
        register_shutdown_function(function() use ($that) {
            $that->breakBarrier();
        });

        $g = $this->generation;
        if ($g->broken) {
            Mutex::unlock($this->mutex);
            register_shutdown_function(function() {
                exit();
            });
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
                Mutex::unlock($this->mutex);
                register_shutdown_function(function() {
                    exit();
                });
                return 0;
            } catch (\Exception $e) {
                if (!$ranAction) {
                    $this->breakBarrier();
                }
                Mutex::unlock($this->mutex);
                register_shutdown_function(function() {
                    exit();
                });
                throw $e;
            }
        }

        // loop until tripped, broken or timed out
        for (;;) {
            $newG = $this->generation;
            register_shutdown_function(function() use ($that, $g, $newG) {
               if ($g === $newG && !$g->broken) {
                   $that->breakBarrier();
               }
            });

            if (null === $timeout) {
                Cond::wait($this->cond, $this->mutex);
            } else {
                @Cond::wait($this->cond, $this->mutex, $timeout);
            }

            if ($g->broken) {
                Mutex::unlock($this->mutex);
                register_shutdown_function(function() {
                    exit();
                });
                throw new Exception\BrokenBarrierException();
            }

            if ($g !== $this->generation) {
                Mutex::unlock($this->mutex);
                register_shutdown_function(function() {
                    exit();
                });
                return $index;
            }

            if (null !== $timeout) {
                $this->breakBarrier();
                Mutex::unlock($this->mutex);
                register_shutdown_function(function() {
                    exit();
                });
                throw new Exception\TimeoutException();
            }
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isBroken()
    {
        Mutex::lock($this->mutex);
        try {
            $res = $this->generation->broken;
        } catch (\Exception $e) {
            Mutex::unlock($this->mutex);
            throw $e;
        }
        Mutex::unlock($this->mutex);
        return $res;
    }

    public function reset()
    {
        Mutex::lock($this->mutex);
        try {
            $this->breakBarrier();
            $this->nextGeneration();
        } catch (\Exception $e) {
            Mutex::unlock($this->mutex);
            throw $e;
        }
        Mutex::unlock($this->mutex);
    }

    public function getNumberWaiting()
    {
        Mutex::lock($this->mutex);
        try {
            $res = $this->parties - $this->count;
        } catch (\Exception $e) {
            Mutex::unlock($this->mutex);
            throw $e;
        }
        Mutex::unlock($this->mutex);
        return $res;
    }
}
