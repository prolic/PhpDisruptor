<?php

namespace PhpDisruptorTest\WaitStrategy;

use PhpDisruptor\WaitStrategy\BusySpinWaitStrategy;

class BusySpinWaitStrategyTest extends AbstractWaitStrategyTestCase
{
    public function testShouldWaitForValue()
    {
        $strategy = new BusySpinWaitStrategy();
        $this->assertWaitForWithDelayOf(50000, $strategy);
    }
}
