<?php

namespace PhpDisruptorTest\WaitStrategy;

use PhpDisruptor\WaitStrategy\YieldingWaitStrategy;

class YieldingWaitStrategyTest extends AbstractWaitStrategyTestCase
{
    public function testShouldWaitForValue()
    {
        $strategy = new YieldingWaitStrategy();
        $this->assertWaitForWithDelayOf(50000, $strategy);
    }
}
