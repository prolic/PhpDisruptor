<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\Exception\InterruptedException;

class AwaiterTwo extends AbstractAwaiter
{
    public $barrier;

    public $micros;

    public $atTheStartingGate;

    public function __construct(CyclicBarrier $barrier, CyclicBarrier $atTheStartingGate, $micros)
    {
        $this->name = 'AwaiterTwo';
        $this->barrier = $barrier;
        $this->micros = $micros;
        $this->atTheStartingGate = $atTheStartingGate;
        $this->result = null;
    }

    public function run()
    {
        $this->toTheStartingGate();
        try {
            $that = $this;
            register_shutdown_function(function() use ($that) {
                $that->setResult(new InterruptedException());
            });
            $this->barrier->await($this->micros);
            register_shutdown_function(function() {
                exit();
            });
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }
}
