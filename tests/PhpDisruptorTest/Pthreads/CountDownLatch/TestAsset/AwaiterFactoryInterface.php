<?php

namespace PhpDisruptorTest\Pthreads\CountDownLatch\TestAsset;

interface AwaiterFactoryInterface
{
    /**
     * @return AbstractAwaiter
     */
    public function getAwaiter();
}
