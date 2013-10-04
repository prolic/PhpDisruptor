<?php

namespace PhpDisruptorTest\TestAsset;

use Stackable;
use Thread;

class TestThread extends Thread
{
    public $stackable;

    public function __construct(Stackable $stackable)
    {
        $this->stackable = $stackable;
    }

    public function run()
    {
        $this->stackable->run();
    }
}
