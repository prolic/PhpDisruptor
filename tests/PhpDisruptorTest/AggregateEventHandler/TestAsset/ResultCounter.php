<?php

namespace PhpDisruptorTest\AggregateEventHandler\TestAsset;

use PhpDisruptor\Pthreads\StackableArray;

class ResultCounter extends StackableArray
{
    /**
     * @var int
     */
    public $count;

    /**
     * @var string
     */
    public $result;

    public function __construct()
    {
        $this->count = 0;
    }

    public function incrementAndGet()
    {
        return ++$this->count;
    }

    public function appendToResult()
    {
        $this->result .= $this->incrementAndGet();
    }

    public function getResult()
    {
        return $this->result;
    }
}
