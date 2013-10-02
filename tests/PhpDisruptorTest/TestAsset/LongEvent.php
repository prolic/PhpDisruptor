<?php

namespace PhpDisruptorTest\TestAsset;

use Stackable;

class LongEvent extends Stackable
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

    public function run()
    {
    }
}
