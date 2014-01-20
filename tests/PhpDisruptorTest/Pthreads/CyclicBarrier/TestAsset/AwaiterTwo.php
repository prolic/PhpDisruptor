<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\TimeUnit;

class AwaiterTwo extends AbstractAwaiter
{
    public $barrier;

    public $millies;

    public function __construct(CyclicBarrier $barrier, $millies)
    {
        parent::__construct();
        $this->barrier = $barrier;
        $this->millies = $millies;
    }

    public function run()
    {
        echo 'starting ' . $this->getName() . PHP_EOL;
        try {
            $this->barrier->await($this->millies);
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }
}
