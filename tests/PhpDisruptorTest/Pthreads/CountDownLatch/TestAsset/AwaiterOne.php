<?php

namespace PhpDisruptorTest\Pthreads\CountDownLatch\TestAsset;

use PhpDisruptor\Pthreads\CountDownLatch;

class AwaiterOne extends AbstractAwaiter
{
    public $latch;

    public $gate;

    public function __construct(CountDownLatch $latch, CountDownLatch $gate)
    {
        $this->latch = $latch;
        $this->gate = $gate;
    }

    public function run()
    {
        $this->gate->countDown();
        try {
            $this->latch->await();
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }
}
