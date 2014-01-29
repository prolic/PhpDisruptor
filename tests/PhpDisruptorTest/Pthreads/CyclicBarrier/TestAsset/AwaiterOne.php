<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptorTest\Pthreads\CyclicBarrier\ToTheStartingGateTrait;
use PhpDisruptorTest\Pthreads\CyclicBarrierTest;

class AwaiterOne extends AbstractAwaiter
{
    public $barrier;

    public $atTheStartingGate;

    public function __construct(CyclicBarrier $barrier, CyclicBarrier $atTheStartingGate)
    {
        $this->name = 'AwaiterOne';
        $this->barrier = $barrier;
        $this->atTheStartingGate = $atTheStartingGate;
    }

    public function run()
    {
        CyclicBarrierTest::toTheStartingGate($this->atTheStartingGate);
        try {
            $this->barrier->await();
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }
}
