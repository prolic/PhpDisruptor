<?php

namespace PhpDisruptor\Pthreads;

use PhpDisruptor\Exception;

class CountDownLatch extends StackableArray
{
    /**
     * @var int
     */
    public $count;

    /**
     * Constructor
     *
     * @param int $count
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($count)
    {
        if ($count < 0) {
            throw new Exception\InvalidArgumentException(
                '$count < 0'
            );
        }
        $this->count = $count;
    }

    /**
     * Causes the current thread to wait until the latch has counted down to zero, unless the thread is interrupted.
     *
     * @param int $timeout
     * @param TimeUnit|null $unit the timeunit of the given timeout
     * @return bool true if the count reached zero and false if the waiting time elapsed before the count reached zero
     */
    public function await($timeout = 0, TimeUnit $unit = null)
    {
        if (null !== $unit) {
            $timeout = $unit->toMicros($timeout);
        }
        $timeoutAt = microtime(true) + ($timeout / 1000000);
        while (0 != $this->count) {
            if ($timeout > 0 && (microtime(true) > $timeoutAt)) {
                return false;
            }
            time_nanosleep(0, 1);
        }
        return true;
    }

    /**
     * Decrements the count of the latch, releasing all waiting threads if the count reaches zero.
     *
     * @return void
     */
    public function countDown()
    {
        --$this->count;
    }

    /**
     * Returns the current count.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Returns a string identifying this latch, as well as its state.
     *
     * @return string
     */
    public function __toString()
    {
        return 'Count = ' . $this->count;
    }
}
