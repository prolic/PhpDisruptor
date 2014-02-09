<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Sequence;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;

class SequenceUpdater extends \Thread
{
    public $sequence;

    public $barrier;

    public $sleepTimeMicros;

    public $waitStrategy;

    public function __construct($sleepTimeMicros, WaitStrategyInterface $waitStrategy)
    {
        $this->sleepTimeMicros = $sleepTimeMicros;
        $this->waitStrategy = $waitStrategy;
        $this->barrier = new CyclicBarrier(2);
        $this->sequence = new Sequence();
    }

    public function run()
    {
        $this->barrier->await();
        if (0 != $this->sleepTimeMicros) {
            $this->wait($this->sleepTimeMicros);
        }
        $this->sequence->incrementAndGet();
        $this->waitStrategy->signalAllWhenBlocking();
    }

    public function waitForStartup()
    {
        $this->barrier->await();
    }

    public function getSequence()
    {
        return $this->sequence;
    }
}
