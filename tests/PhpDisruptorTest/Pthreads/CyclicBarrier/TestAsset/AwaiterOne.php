<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptorTest\Pthreads\CyclicBarrierTest;

class AwaiterOne extends AbstractAwaiter
{
    public $barrier;

    public function __construct(CyclicBarrier $barrier)
    {
        $this->barrier = $barrier;
        parent::__construct();
    }

    public function run()
    {
        try {
            $this->barrier->await();
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }
}
