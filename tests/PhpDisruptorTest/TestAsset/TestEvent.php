<?php

namespace PhpDisruptorTest\TestAsset;

use Stackable;

final class TestEvent extends Stackable
{
    public function __toString()
    {
        return 'Test Event';
    }
}
