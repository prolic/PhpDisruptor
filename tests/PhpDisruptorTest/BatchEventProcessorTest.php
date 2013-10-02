<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\TestAsset\EventHandler;
use PhpDisruptorTest\TestAsset\StubEventFactory;
use PhpDisruptorTest\TestAsset\TestThread;
use PhpDisruptorTest\TestAsset\TestWorker;

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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventHandler;

    /**
     * @var BatchEventProcessor
     */
    protected $batchEventProcessor;

    protected function setUp()
    {
        $this->ringBuffer = RingBuffer::createMultiProducer(new StubEventFactory(), 16);
        $this->sequenceBarrier = $this->ringBuffer->newBarrier();
        $this->eventHandler = new EventHandler('PhpDisruptorTest\TestAsset\StubEvent');
        $this->batchEventProcessor = new BatchEventProcessor('PhpDisruptorTest\TestAsset\StubEvent', $this->ringBuffer, $this->sequenceBarrier, $this->eventHandler);
    }

    public function testFoo()
    {
        $this->assertTrue(true);
    }


    public function testShouldCallMethodsInLifecycleOrder()
    {
        if (file_exists(sys_get_temp_dir() . '/testresult')) {
            unlink(sys_get_temp_dir() . '/testresult');
        }

        $thread = new TestWorker();
        $thread->start();
        $thread->stack($this->batchEventProcessor);

        $this->assertEquals(-1, $this->batchEventProcessor->getSequence()->get());
        $this->ringBuffer->publish($this->ringBuffer->next());

        time_nanosleep(0, 45000);
        $this->batchEventProcessor->halt();
        $thread->shutdown();

        $result = file_get_contents(sys_get_temp_dir() . '/testresult');
        $this->assertEquals('PhpDisruptorTest\TestAsset\StubEvent-0-1', $result);

        if (file_exists(sys_get_temp_dir() . '/testresult')) {
            unlink(sys_get_temp_dir() . '/testresult');
        }
    }
}
