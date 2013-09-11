<?php

namespace PhpDisruptorTest\TestAsset;

use Thread;

class TestThread extends Thread
{
    protected $test;

    public function __construct($test){
        $this->test = $test;
        $this->start();
    }
    public function run()
    {
    }
}
