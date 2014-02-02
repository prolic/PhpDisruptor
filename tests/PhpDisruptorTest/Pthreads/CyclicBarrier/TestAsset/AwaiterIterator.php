<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\StackableArray;

/**
 * Returns an infinite lazy list of all possible awaiter pair combinations.
 */
class AwaiterIterator extends StackableArray
{
    public $i;

    public $barrier;

    public $atTheStartingGate;

    public function __construct(CyclicBarrier $barrier, CyclicBarrier $atTheStartingGate)
    {
        $this->i = 0;
        $this->barrier = $barrier;
        $this->atTheStartingGate = $atTheStartingGate;
    }

    public function next()
    {
        switch ($this->i++ & 7) {
            case 0:
            case 2:
            case 4:
            case 5:
                return new AwaiterOne($this->barrier, $this->atTheStartingGate);
            default:
                return new AwaiterTwo($this->barrier, $this->atTheStartingGate, 10 * 1000 * 1000);
        }
    }
}
