<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\Lists\SequenceList;
use ConcurrentPhpUtils\CountDownLatch;
use PhpDisruptor\RingBuffer;
use PhpDisruptorTest\TestAsset\StubEventFactory;
use PhpDisruptorTest\TestAsset\TestSequenceReportingEventHandler;

class SequenceReportingCallbackTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReportProgressByUpdatingSequenceViaCallback()
    {
        $callbackLatch = new CountDownLatch(1);
        $onEndOfBatchLatch = new CountDownLatch(1);

        $eventFactory = new StubEventFactory();
        $ringBuffer = RingBuffer::createMultiProducer($eventFactory, 16);
        $sequenceBarrier = $ringBuffer->newBarrier();
        $handler = new TestSequenceReportingEventHandler($callbackLatch);
        $batchEventProcessor = new BatchEventProcessor($eventFactory->getEventClass(), $ringBuffer, $sequenceBarrier, $handler);

        $sequenceList = new SequenceList($batchEventProcessor->getSequence());
        $ringBuffer->addGatingSequences($sequenceList);

        $batchEventProcessor->start();

        $this->assertSame(-1, $batchEventProcessor->getSequence()->get());
        $ringBuffer->publish($ringBuffer->next());

        $callbackLatch->await();
        $this->assertSame(0, $batchEventProcessor->getSequence()->get());

        $onEndOfBatchLatch->countDown();
        $this->assertSame(0, $batchEventProcessor->getSequence()->get());

        $batchEventProcessor->halt();
        $batchEventProcessor->join();
    }
}
