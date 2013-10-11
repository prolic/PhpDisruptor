<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\Pthreads\StackableArray;
use Stackable;

class ArrayFactory extends Stackable implements EventFactoryInterface
{
    public $size;

    public function run()
    {
    }

    public function __construct($size)
    {
        $this->size = $size;
    }

    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return 'StackableArray';
    }

    public function newInstance()
    {
        $array = new StackableArray();
        for ($i = 0; $i < $this->size; $i++) {
            $array[] = new StackableArray();
        }
    }
}
