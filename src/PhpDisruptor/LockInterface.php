<?php

namespace PhpDisruptor;

use DateInterval;

interface LockInterface
{
    /**
     * Acquires the lock.
     *
     * @return void
     */
    public function lock();

    /**
     * Acquires the lock if it is free within the given waiting time and the current thread has not been interrupted.
     *
     * @param DateInterval $interval
     * @return bool
     */
    public function tryLock(DateInterval $interval);

    /**
     * Releases the lock.
     *
     * @return void
     */
    public function unLock();
}
