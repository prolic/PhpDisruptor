<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\Pthreads\CountDownLatch;
use PhpDisruptor\Sequence;

class CountDownLatchSequence extends Sequence
{
    /**
     * @var CountDownLatch
     */
    public $latch;

    /**
     * @param int $initialValue
     * @param CountDownLatch $latch
     */
    public function __construct($initialValue, CountDownLatch $latch)
    {
        parent::__construct($initialValue);
        $this->latch = $latch;
    }

    /**
     * @return int
     */
    public function get()
    {
        $this->latch->countDown();
        return parent::get();
    }
}
