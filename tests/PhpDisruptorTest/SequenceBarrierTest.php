<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptorTest\TestAsset\EventProcessor;
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
        $this->eventProcessor1 = new EventProcessor();
        $this->eventProcessor2 = new EventProcessor();
        $this->eventProcessor3 = new EventProcessor();
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
