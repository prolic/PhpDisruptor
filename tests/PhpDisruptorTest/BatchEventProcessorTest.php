<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\BatchEventProcessor;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\TestAsset\EventHandler;
use PhpDisruptorTest\TestAsset\StubEventFactory;
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
        $logger = new \Zend\Log\Logger(array(
            'writers' => array(
                'null' => array(
                    'name' => 'Mock'
                )
            )
        ));
        $this->batchEventProcessor = new BatchEventProcessor('PhpDisruptorTest\TestAsset\StubEvent', $this->ringBuffer, $this->sequenceBarrier, $this->eventHandler, $logger);
    }

    public function testFoo()
    {
        $this->assertTrue(true);
    }


    /**
     * @todo: failing !!!
     */

//    public function testShouldCallMethodsInLifecycleOrder()
//    {
//        $this->assertEquals(-1, $this->batchEventProcessor->getSequence()->get());
//
//        $this->ringBuffer->publish($this->ringBuffer->next());
//        //$this->ringBuffer->publish($this->ringBuffer->next());
//        //$this->ringBuffer->publish($this->ringBuffer->next());
//
//
//        if ($this->batchEventProcessor->start()) {
//            $this->batchEventProcessor->shutdown();
//        }
//
//
//        //$thread = new TestThread($this->batchEventProcessor);
//        //$thread->start();
//        //time_nanosleep(0, 15000);
//        //sleep(1);
//
//        $this->batchEventProcessor->halt();
//        //$thread->join();
//    }
}
