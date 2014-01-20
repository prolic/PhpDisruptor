<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Pthreads\TimeUnit;

class FunTwo extends StackableArray
{
    /**
     * @var CyclicBarrier
     */
    public $barrier;

    public function __construct(CyclicBarrier $barrier)
    {
        $this->barrier = $barrier;
    }

    public function f()
    {
        $this->barrier->await(100, TimeUnit::MILLISECONDS());
    }
}
