<?php

namespace PhpDisruptor\Pthreads;

use Cond;
use Mutex;
use Thread;

// @todo: check why this is required
require_once __DIR__ . '/Exception/BrokenBarrierException.php';
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
     * @param int $mutex
     * @param int $cond
     * @param int $parties
     * @param Thread|null $barrierAction
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($mutex, $cond, $parties, Thread $barrierAction = null)
    {
        if ($parties <= 0) {
            throw new Exception\InvalidArgumentException();
        }
        $this->parties = $parties;
        $this->count = $parties;
        $this->barrierCommand = $barrierAction;
        $this->mutex = $mutex;
        $this->cond = $cond;
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
     */
    public function nextGeneration()
    {
        // signal completion of last generation
        Cond::broadcast($this->cond);

        // set up next generation
        $this->count = $this->parties;
        $this->generation = new Generation();
    }

    public function breakBarrier()
    {
        $this->generation->broken = true;
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
     */

    /**
     * @param null $timeout
     * @return int
     * @throws Exception\InvalidArgumentException
     * @throws \Exception
     */
    public function await($timeout = null)
    {
        if (null !== $timeout && (!is_numeric($timeout) || $timeout < 1)) {
            throw new Exception\InvalidArgumentException(
                '$timeout (in microseconds) must be a positive integer or null'
            );
        }

        var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' locking');
        Mutex::lock($this->mutex);
        var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' locking ok');
        if ($this->generation->broken) {
            var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' generation broken');
            Mutex::unlock($this->mutex);
            throw new Exception\BrokenBarrierException();
        } else {

            var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' generation ok');
        }

        $index = --$this->count;

        var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' test index: ' . $index);
        if ($index == 0) { // tripped
            var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' index = 0, tripped ');
            $ranAction = false;

            try {
                if (null !== $this->barrierCommand) {
                    $this->barrierCommand->start();
                }
                $ranAction = true;
                var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' next generation, old: ' . var_export($this->generation, 1));
                $this->nextGeneration();
                var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' next generation ok, unlocking..., new: '. var_export($this->generation, 1));
                Mutex::unlock($this->mutex);
                var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' unlocking ok');
                time_nanosleep(0,10000000);
                return 0;
            } catch (\Exception $e) {
                if (!$ranAction) {
                    $this->breakBarrier();
                }
                Mutex::unlock($this->mutex);
                throw $e;
            }
        }

        // loop until tripped, broken or timed out
        for (;;) {
            time_nanosleep(0, 100000);
            var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' waiting....');
            time_nanosleep(0, 100000);
            if (null === $timeout) {
                Cond::wait($this->cond, $this->mutex);
            } else {
                @Cond::wait($this->cond, $this->mutex, $timeout);
            }
            time_nanosleep(0, 100000);

            var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' waiting ok');

            if ($this->generation->broken) {
                var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' generation broken');
                Mutex::unlock($this->mutex);
                throw new Exception\BrokenBarrierException();
            } else {
                var_dump(microtime(1) . ' ' . $this->name . ': ' . Thread::getCurrentThreadId() . ' generation ok');
            }

            if ($this->generation !== $this->generation) {
                var_dump(microtime(1) . ' ' . 'HUCH!!!!!!!!!!!!!!');
                Mutex::unlock($this->mutex);
                return $index;
            }

            if (null !== $timeout) {
                $this->breakBarrier();
                Mutex::unlock($this->mutex);
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
