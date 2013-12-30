<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\TestAsset\EventHandler;
use PhpDisruptorTest\TestAsset\ExEventHandler;
use PhpDisruptorTest\TestAsset\StubEventFactory;
use PhpDisruptorTest\TestAsset\TestExceptionHandler;
use PhpDisruptorTest\TestAsset\TestThread;

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

    protected function setUp()
    {
        $factory = new StubEventFactory();
        $this->ringBuffer = RingBuffer::createMultiProducer($factory, 16);
        $this->sequenceBarrier = $this->ringBuffer->newBarrier();

        if (file_exists(sys_get_temp_dir() . '/testresult')) {
            unlink(sys_get_temp_dir() . '/testresult');
        }
    }

    protected function tearDown()
    {
        if (file_exists(sys_get_temp_dir() . '/testresult')) {
            unlink(sys_get_temp_dir() . '/testresult');
        }
    }

    public function testShouldCallMethodsInLifecycleOrder()
    {
        $eventHandler = new EventHandler('PhpDisruptorTest\TestAsset\StubEvent');
        $batchEventProcessor = new BatchEventProcessor(
            'PhpDisruptorTest\TestAsset\StubEvent',
            $this->ringBuffer,
            $this->sequenceBarrier,
            $eventHandler
        );

        $batchEventProcessor->start();

        $this->assertEquals(-1, $batchEventProcessor->getSequence()->get());
        $this->ringBuffer->publish($this->ringBuffer->next());

        time_nanosleep(0, 45000);
        $batchEventProcessor->halt();
        $batchEventProcessor->join();

        $result = file_get_contents(sys_get_temp_dir() . '/testresult');
        $this->assertEquals('PhpDisruptorTest\TestAsset\StubEvent-0-1', $result);
    }

    public function testShouldCallMethodsInLifecycleOrderForBatch()
    {
        $eventHandler = new EventHandler('PhpDisruptorTest\TestAsset\StubEvent');
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

        time_nanosleep(0, 75000);
        $batchEventProcessor->halt();
        $batchEventProcessor->join();

        $result = file_get_contents(sys_get_temp_dir() . '/testresult');

        $this->assertEquals(
            'PhpDisruptorTest\TestAsset\StubEvent-0-0PhpDisruptorTest\TestAsset\StubEvent-1-0'
            . 'PhpDisruptorTest\TestAsset\StubEvent-2-1',
            $result
        );
    }

    public function testShouldCallExceptionHandlerOnUncaughtException()
    {
        $exceptionHandler = new TestExceptionHandler();
        $eventHandler = new ExEventHandler('PhpDisruptorTest\TestAsset\StubEvent');
        $batchEventProcessor = new BatchEventProcessor(
            'PhpDisruptorTest\TestAsset\StubEvent',
            $this->ringBuffer,
            $this->sequenceBarrier,
            $eventHandler
        );
        $batchEventProcessor->setExceptionHandler($exceptionHandler);

        $batchEventProcessor->start();

        $this->ringBuffer->publish($this->ringBuffer->next());

        time_nanosleep(0, 45000);
        $batchEventProcessor->halt();
        $batchEventProcessor->join();

        $result = file_get_contents(sys_get_temp_dir() . '/testresult');

        $this->assertEquals(
            'PhpDisruptorTest\TestAsset\TestExceptionHandler::'
            . 'handleEventExceptionException-0-PhpDisruptorTest\TestAsset\StubEvent',
            $result
        );
    }
}
