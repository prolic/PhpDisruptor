<?php

namespace PhpDisruptorTest\LifecycleAwareInterface;

use PhpDisruptor\EventProcessor\BatchEventProcessor;
use ConcurrentPhpUtils\CountDownLatch;
use PhpDisruptor\RingBuffer;
use PhpDisruptorTest\LifecycleAwareInterface\TestAsset\LifecycleAwareEventHandler;
use PhpDisruptorTest\TestAsset\StubEventFactory;

class LifecycleAwareInterfaceTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldNotifyOfBatchProcessorLifecycle()
    {
        $startLatch = new CountDownLatch(1);
        $shutdownLatch = new CountDownLatch(1);

        $eventFactory = new StubEventFactory();

        $ringBuffer = RingBuffer::createMultiProducer($eventFactory, 16);
        $sequenceBarrier = $ringBuffer->newBarrier();

        $handler = new LifecycleAwareEventHandler($startLatch, $shutdownLatch);

        $batchEventProcessor = new BatchEventProcessor(
            $eventFactory->getEventClass(),
            $ringBuffer,
            $sequenceBarrier,
            $handler
        );

        $batchEventProcessor->start();

        $startLatch->await();
        $batchEventProcessor->halt();

        $shutdownLatch->await();

        $this->assertSame(1, $handler->startCounter);
        $this->assertSame(1, $handler->shutdownCounter);
    }
}
