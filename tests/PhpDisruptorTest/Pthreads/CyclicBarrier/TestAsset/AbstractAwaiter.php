<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use Thread;

abstract class AbstractAwaiter extends Thread
{
    public $name;

    public $result;

    public function setResult(\Exception $result)
    {
        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getName()
    {
        return $this->name;
    }
}
