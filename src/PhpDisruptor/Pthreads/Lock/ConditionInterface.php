<?php

namespace PhpDisruptor\Pthreads\Lock;

use PhpDisruptor\Pthreads\TimeUnit;

interface ConditionInterface
{
    /**
     * @param int|null $time
     * @param TimeUnit|null $unit
     * @return bool
     */
    public function await($time = null, TimeUnit $unit = null);

    /**
     * @return void
     */
    public function signal();

    /**
     * @return void
     */
    public function signalAll();
}
