<?php

namespace PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset;

use Thread;

abstract class AbstractAwaiter extends Thread
{
    public static $count;

    public $name;

    public $result;

    public function __construct()
    {
        self::$count = 1;
        $this->name = 'Awaiter' + self::$count++;
    }

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
