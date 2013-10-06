<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\TestAsset\StubEvent;
use PhpDisruptorTest\TestAsset\StubEventFactory;
use PhpDisruptorTest\TestAsset\StubEventTranslator;

class RingBufferTest extends \PHPUnit_Framework_TestCase
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
        $eventFactory = new StubEventFactory();
        $this->ringBuffer = RingBuffer::createMultiProducer($eventFactory, 32);
        $this->sequenceBarrier = $this->ringBuffer->newBarrier();

        $eventProcessor = new NoOpEventProcessor($this->ringBuffer);
        $sequences = new StackableArray();
        $sequences[] = $eventProcessor->getSequence();
        $this->ringBuffer->addGatingSequences($sequences);
    }

    public function testShouldClaimAndGet()
    {
        $this->assertEquals(Sequence::INITIAL_VALUE, $this->ringBuffer->getCursor());

        $eventTranslator = new StubEventTranslator();
        $expectedEvent = new StubEvent(2701);
        $args = new StackableArray();
        $args[] = $expectedEvent->getValue();
        $args[] = $expectedEvent->getTestString();
        $this->ringBuffer->publishEvent($eventTranslator, $args);

        $sequence = $this->sequenceBarrier->waitFor(0);
        $this->assertEquals(0, $sequence);

        $event = $this->ringBuffer->get($sequence);
        $this->assertEquals($expectedEvent, $event);

        $this->assertEquals(0, $this->ringBuffer->getCursor());
    }
}
