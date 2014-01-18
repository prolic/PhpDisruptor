<?php

namespace PhpDisruptor\Pthreads;

/**
 * internal class to use in CyclicBarrier implementation
 */
class Generation extends StackableArray
{
    /**
     * @var bool
     */
    public $broken;

    public function __construct()
    {
        $this->broken = false;
    }

    public function setBroken()
    {
        $this->broken = true;
    }

    public function broken()
    {
        return $this->broken;
    }
}
