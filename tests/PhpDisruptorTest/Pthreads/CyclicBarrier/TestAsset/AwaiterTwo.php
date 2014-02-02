<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptorTest\Pthreads\CyclicBarrier\ToTheStartingGateTrait;
use PhpDisruptorTest\Pthreads\CyclicBarrierTest;

class AwaiterTwo extends AbstractAwaiter
{
    public $barrier;

    public $millies;

    public $atTheStartingGate;

    public function __construct(CyclicBarrier $barrier, CyclicBarrier $atTheStartingGate, $millies)
    {
        $this->name = 'AwaiterTwo';
        $this->barrier = $barrier;
        $this->millies = $millies;
        $this->atTheStartingGate = $atTheStartingGate;
    }

    public function run()
    {
        $this->toTheStartingGate();
        try {
            $this->barrier->await($this->millies);
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }
}
