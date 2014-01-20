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
        $this->barrier = $barrier;
        $this->millies = $millies;
        parent::__construct();
    }

    public function run()
    {
        try {
            $unit = TimeUnit::get(2);
            $f = fopen('/tmp/foobar', 'w+');
            fwrite($f, serialize($unit));
            fclose($f);
            var_dump($unit); die;
            $this->barrier->await($this->millies, $unit);
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }
}
