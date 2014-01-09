<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\Lists\EventProcessorList;
use PhpDisruptor\Pthreads\CountDownLatch;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;

class RingBufferThread2 extends \Thread
{
    public $ringBuffer;

    public $latch;

    public $publisherComplete;

    public function __construct(RingBuffer $ringBuffer, CountDownLatch $latch, StackableArray $publisherComplete)
    {
        $this->ringBuffer = $ringBuffer;
        $this->latch = $latch;
        $this->publisherComplete = $publisherComplete;
    }

    public function run()
    {
        $ringBuffer = $this->ringBuffer;
        for ($i = 0; $i <= $ringBuffer->getBufferSize(); $i++) {
            $sequence = $ringBuffer->next();
            $event = $ringBuffer->get($sequence);
            /* @var $event StubEvent */
            $event->setValue($i);
            $ringBuffer->publish($sequence);
            $this->latch->countDown();
        }
        $this->publisherComplete[0] = true;
    }
}
