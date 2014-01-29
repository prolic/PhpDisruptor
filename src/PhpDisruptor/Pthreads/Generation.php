<?php

namespace PhpDisruptor\Pthreads;

/**
 * internal class to use in CyclicBarrier implementation
 */
class Generation extends StackableArray
{
    public $broken;

    public function __construct()
    {
        $this->broken = false;
    }
}
