<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptorTest\Pthreads\CyclicBarrierTest;

class AwaiterOne extends AbstractAwaiter
{
    public $barrier;

    public function __construct(CyclicBarrier $barrier)
    {
        parent::__construct();
        $this->barrier = $barrier;
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
