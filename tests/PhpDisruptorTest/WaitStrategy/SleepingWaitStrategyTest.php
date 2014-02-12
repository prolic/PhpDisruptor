<?php

namespace PhpDisruptorTest\WaitStrategy;

use PhpDisruptor\WaitStrategy\SleepingWaitStrategy;

class SleepingWaitStrategyTest extends AbstractWaitStrategyTestCase
{
    public function testShouldWaitForValue()
    {
        $strategy = new SleepingWaitStrategy();
        $this->assertWaitForWithDelayOf(50000, $strategy);
    }
}
