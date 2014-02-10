<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\Pthreads\CountDownLatch;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\TestAsset\LifecycleAwareEventHandler;
use PhpDisruptorTest\TestAsset\StubEventFactory;

class LifecycleAwareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CountDownLatch
     */
    private $startLatch;

    /**
     * @var CountDownLatch
     */
    private $shutdownLatch;

    /**
     * @var RingBuffer
     */
    private $ringBuffer;

    /**
     * @var SequenceBarrierInterface
     */
    private $sequenceBarrier;

    /**
     * @var LifecycleAwareEventHandler
     */
    private $handler;

    /**
     * @var BatchEventProcessor
     */
    private $batchEventProcessor;

    protected function setUp()
    {
        $this->startLatch = new CountDownLatch(1);
        $this->shutdownLatch = new CountDownLatch(1);

        $eventFactory = new StubEventFactory();

        $this->ringBuffer = RingBuffer::createMultiProducer($eventFactory, 16);
        $this->sequenceBarrier = $this->ringBuffer->newBarrier();
        $this->handler = new LifecycleAwareEventHandler($this->startLatch, $this->shutdownLatch);
        $this->batchEventProcessor = new BatchEventProcessor(
            $eventFactory->getEventClass(),
            $this->ringBuffer,
            $this->sequenceBarrier,
            $this->handler
        );
    }

    public function testShouldNotifyOfBatchProcessorLifecycle()
    {
        $this->batchEventProcessor->start();

        $this->startLatch->await();
        $this->batchEventProcessor->halt();

        $this->shutdownLatch->await();

        $this->assertSame(1, $this->handler->startCounter);
        $this->assertSame(1, $this->handler->shutdownCounter);
    }
}
