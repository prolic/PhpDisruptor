<?php

namespace PhpDisruptor\Pthreads\Lock;

use PhpDisruptor\Pthreads\TimeUnit;

interface LockInterface
{
    /**
     * @return void
     */
    public function lock();

    /**
     * @param int|null $time
     * @param TimeUnit|null $unit
     * @return bool
     */
    public function tryLock($time = null, TimeUnit $unit = null);

    /**
     * @return void
     */
    public function unlock();

    /**
     * @return ConditionInterface
     */
    public function newCondition();
}
