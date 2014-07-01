<?php

namespace PhpDisruptorTest\AggregateEventHandler\TestAsset;

use Threaded;

class ResultCounter extends Threaded
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
