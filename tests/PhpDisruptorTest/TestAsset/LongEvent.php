<?php

namespace PhpDisruptorTest\TestAsset;

use Threaded;

class LongEvent extends Threaded
{
    public $long;

    public function __construct()
    {
        $this->long = 0;
    }

    public function set($long)
    {
        $this->long = $long;
    }

    public function get()
    {
        return $this->long;
    }
}
