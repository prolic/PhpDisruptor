<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventFactoryInterface;
use Threaded;

class ArrayFactory extends Threaded implements EventFactoryInterface
{
    public $size;

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
        return 'Threaded';
    }

    public function newInstance()
    {
        $array = new Threaded();
        for ($i = 0; $i < $this->size; $i++) {
            $array[] = null;
        }
        return $array;
    }
}
