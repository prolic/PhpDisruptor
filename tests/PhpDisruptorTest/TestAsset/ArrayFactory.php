<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\EventFactoryInterface;
use ConcurrentPhpUtils\NoOpStackable;
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
        return 'ConcurrentPhpUtils\NoOpStackable';
    }

    public function newInstance()
    {
        $array = new NoOpStackable();
        for ($i = 0; $i < $this->size; $i++) {
            $array[] = null;
        }
        return $array;
    }
}
