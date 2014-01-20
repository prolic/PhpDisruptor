<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\StackableArray;

class AwaiterIterator extends StackableArray
{
    protected $i = 0;

    public $barrier;

    public function __construct(CyclicBarrier $barrier)
    {
        $this->barrier = $barrier;
    }

    public function next()
    {
        switch ($this->i++ & 7) {
            case 0:
            case 2:
            case 4:
            case 5:
                return new AwaiterOne($this->barrier);
            default:
                return new AwaiterTwo($this->barrier, 10 * 1000);
        }
    }
}
