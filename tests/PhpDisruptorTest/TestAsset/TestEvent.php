<?php

namespace PhpDisruptorTest\TestAsset;

use Threaded;

final class TestEvent extends Threaded
{
    public function __toString()
    {
        return 'Test Event';
    }
}
