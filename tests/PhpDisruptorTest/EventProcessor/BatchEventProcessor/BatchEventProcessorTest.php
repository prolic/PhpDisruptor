<?php

namespace PhpDisruptorTest\EventProcessor;

use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\Pthreads\CountDownLatch;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\BatchEventProcessor\TestAsset\EventHandler;
use PhpDisruptorTest\BatchEventProcessor\TestAsset\ExEventHandler;
use PhpDisruptorTest\TestAsset\StubEventFactory;
use PhpDisruptorTest\TestAsset\TestExceptionHandler;

class BatchEventProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RingBuffer
     */
    protected $ringBuffer;

    /**
     * @var SequenceBarrierInterface
     */
    protected $sequenceBarrier;

    /**
     * @var CountDownLatch
     */
    protected $latch;

    protected function setUp()
    {
        $factory = new StubEventFactory();
        $this->ringBuffer = RingBuffer::createMultiProducer($factory, 16);
        $this->sequenceBarrier = $this->ringBuffer->newBarrier();
        $this->latch = new CountDownLatch(1);
    }

    public function testShouldCallMethodsInLifecycleOrder()
    {
        $eventHandler = new EventHandler('PhpDisruptorTest\TestAsset\StubEvent', $this->latch);
        $batchEventProcessor = new BatchEventProcessor(
            'PhpDisruptorTest\TestAsset\StubEvent',
            $this->ringBuffer,
            $this->sequenceBarrier,
            $eventHandler
        );

        $batchEventProcessor->start();

        $this->assertEquals(-1, $batchEventProcessor->getSequence()->get());
        $this->ringBuffer->publish($this->ringBuffer->next());

        $this->latch->await();

        $batchEventProcessor->halt();
        $batchEventProcessor->join();

        $expectedResult = new StackableArray();
        $expectedResult[] = 'onStart';
        $expectedResult[] = 'PhpDisruptorTest\TestAsset\StubEvent-0-1';
        $expectedResult[] = 'onShutdown';

        $this->assertEquals($expectedResult, $eventHandler->getResult());
    }

    public function testShouldCallMethodsInLifecycleOrderForBatch()
    {
        $eventHandler = new EventHandler('PhpDisruptorTest\TestAsset\StubEvent', $this->latch);
        $batchEventProcessor = new BatchEventProcessor(
            'PhpDisruptorTest\TestAsset\StubEvent',
            $this->ringBuffer,
            $this->sequenceBarrier,
            $eventHandler
        );

        $this->ringBuffer->publish($this->ringBuffer->next());
        $this->ringBuffer->publish($this->ringBuffer->next());
        $this->ringBuffer->publish($this->ringBuffer->next());

        $batchEventProcessor->start();

        $this->latch->await();

        $batchEventProcessor->halt();
        $batchEventProcessor->join();

        $expectedResult = new StackableArray();
        $expectedResult[] = 'onStart';
        $expectedResult[] = 'PhpDisruptorTest\TestAsset\StubEvent-0-0';
        $expectedResult[] = 'PhpDisruptorTest\TestAsset\StubEvent-1-0';
        $expectedResult[] = 'PhpDisruptorTest\TestAsset\StubEvent-2-1';
        $expectedResult[] = 'onShutdown';

        $this->assertEquals($expectedResult, $eventHandler->getResult());
    }

    public function testShouldCallExceptionHandlerOnUncaughtException()
    {
        $exceptionHandler = new TestExceptionHandler();
        $eventHandler = new ExEventHandler('PhpDisruptorTest\TestAsset\StubEvent', $this->latch);
        $batchEventProcessor = new BatchEventProcessor(
            'PhpDisruptorTest\TestAsset\StubEvent',
            $this->ringBuffer,
            $this->sequenceBarrier,
            $eventHandler
        );
        $batchEventProcessor->setExceptionHandler($exceptionHandler);

        $batchEventProcessor->start();

        $this->ringBuffer->publish($this->ringBuffer->next());

        $this->latch->await();

        $batchEventProcessor->halt();
        $batchEventProcessor->join();

        $expectedResult = new StackableArray();
        $expectedResult[] = 'onStart';
        $expectedResult[] = 'onShutdown';

        $this->assertEquals($expectedResult, $eventHandler->getResult());

        $expectedException = new StackableArray();
        $expectedException[] = 'PhpDisruptorTest\TestAsset\TestExceptionHandler::handleEventExceptionException-0-PhpDisruptorTest\TestAsset\StubEvent';

        $this->assertEquals($expectedException, $exceptionHandler->getResult());
    }
}
