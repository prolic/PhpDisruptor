<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\Util\Util;
use PhpDisruptorTest\TestAsset\RingBufferThread;
use PhpDisruptorTest\TestAsset\StubEventProcessor;
use PhpDisruptorTest\TestAsset\StubEventFactory;

class SequenceBarrierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RingBuffer
     */
    protected $ringBuffer;

    protected $eventProcessor1;
    protected $eventProcessor2;
    protected $eventProcessor3;

    protected function setUp()
    {
        $this->eventProcessor1 = new StubEventProcessor();
        $this->eventProcessor2 = new StubEventProcessor();
        $this->eventProcessor3 = new StubEventProcessor();
        $eventFactory = new StubEventFactory();
        $this->ringBuffer = RingBuffer::createMultiProducer($eventFactory, 64);
        $eventProcessor = new NoOpEventProcessor($this->ringBuffer);
        $this->ringBuffer->addGatingSequences(array($eventProcessor->getSequence()));
    }

    public function testShouldWaitForWorkCompleteWhereCompleteWorkThresholdIsAhead()
    {
        $expectedNumberMessages = 10;
        $expectedWorkSequence = 9;
        $this->fillRingBuffer($expectedNumberMessages);

        $sequence1 = new Sequence($expectedNumberMessages);
        $sequence2 = new Sequence($expectedWorkSequence);
        $sequence3 = new Sequence($expectedNumberMessages);

        $this->eventProcessor1->setSequence($sequence1);
        $this->eventProcessor2->setSequence($sequence2);
        $this->eventProcessor3->setSequence($sequence3);

        $sequenceBarrier = $this->ringBuffer->newBarrier(array(
            $this->eventProcessor1->getSequence(),
            $this->eventProcessor2->getSequence(),
            $this->eventProcessor3->getSequence()
        ));

        $completedWorkSequence = $sequenceBarrier->waitFor($expectedWorkSequence);
        $this->assertTrue($completedWorkSequence >= $expectedWorkSequence);
    }

    public function testShouldWaitForWorkCompleteWhereAllWorkersAreBlockedOnRingBuffer()
    {
        $expectedNumberMessages = 10;
        $this->fillRingBuffer($expectedNumberMessages);

        $workers = array();
        for ($i = 0; $i < 3; $i++) {
            $worker = $workers[$i] = new StubEventProcessor();
            $worker->setSequence($expectedNumberMessages - 1);
        }

        $sequenceBarrier = $this->ringBuffer->newBarrier(Util::getSequencesFor($workers));
        $thread = new RingBufferThread($this->ringBuffer, $workers);
        $thread->start();

        $expectedWorkSequence = $expectedNumberMessages;
        $completedWorkSequence = $sequenceBarrier->waitFor($expectedNumberMessages);
        $this->assertTrue($completedWorkSequence >= $expectedWorkSequence);
    }

    protected function fillRingBuffer($expectedNumberMessages)
    {
        for ($i = 0; $i < $expectedNumberMessages; $i++) {
            $sequence = $this->ringBuffer->next();
            $event = $this->ringBuffer->get($sequence);
            $event->setValue($i);
            $this->ringBuffer->publish($sequence);
        }
    }
}
