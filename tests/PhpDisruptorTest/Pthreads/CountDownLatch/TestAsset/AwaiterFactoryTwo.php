<?php

namespace PhpDisruptorTest\Pthreads\CountDownLatch\TestAsset;

use PhpDisruptor\Pthreads\CountDownLatch;

class AwaiterFactoryTwo implements AwaiterFactoryInterface
{
    public $latch;

    public $gate;

    public $int;

    public function __construct(CountDownLatch $latch, CountDownLatch $gate, $int)
    {
        $this->latch = $latch;
        $this->gate = $gate;
        $this->int = $int;
    }

    /**
     * @return AbstractAwaiter
     */
    public function getAwaiter()
    {
        return new AwaiterTwo($this->latch, $this->gate, $this->int);
    }
}
