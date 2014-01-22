<?php

namespace PhpDisruptor\Pthreads;

/**
 * internal class to use in CyclicBarrier implementation
 */
class Generation extends StackableArray
{
    public function __construct()
    {
        $this[0] = false;
    }

    public function setBroken()
    {
        $this[0] = true;
    }

    public function broken()
    {
        return $this[0];
    }
}
