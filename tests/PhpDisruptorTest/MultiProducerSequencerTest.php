<?php

namespace PhpDisruptorTest;

use PhpDisruptor\MultiProducerSequencer;
use PhpDisruptor\WaitStrategy\BlockingWaitStrategy;

class MultiProducerSequencerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldOnlyAllowMessagesToBeAvailableIfSpecificallyPublished()
    {
        $publisher = new MultiProducerSequencer(1024, new BlockingWaitStrategy());
        $publisher->publish(3);
        $publisher->publish(5);
        
        $this->assertFalse($publisher->isAvailable(0));
        $this->assertFalse($publisher->isAvailable(1));
        $this->assertFalse($publisher->isAvailable(2));
        $this->assertTrue($publisher->isAvailable(3));
        $this->assertFalse($publisher->isAvailable(4));
        $this->assertTrue($publisher->isAvailable(5));
        $this->assertFalse($publisher->isAvailable(6));
    }
}
