<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\Exception\InsufficientCapacityException;
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
     * @var EventFactoryInterface
     */
    protected $eventFactory;

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
        $this->eventFactory = new StubEventFactory();
        $this->ringBuffer = RingBuffer::createMultiProducer($this->eventFactory, 32);
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

    public function testShouldClaimAndGetMultipleMessages()
    {
        $eventTranslator = new StubEventTranslator();
        $numMessages = $this->ringBuffer->getBufferSize();
        for ($i = 0; $i < $numMessages; $i++) {
            $args = new StackableArray();
            $args[] = $i;
            $args[] = '';
            $this->ringBuffer->publishEvent($eventTranslator, $args);
        }

        $expectedSequence = $numMessages - 1;
        $available = $this->sequenceBarrier->waitFor($expectedSequence);
        $this->assertEquals($expectedSequence, $available);

        for ($i = 0; $i < $numMessages; $i++) {
            $this->assertEquals($i, $this->ringBuffer->get($i)->getValue());
        }
    }

    public function testShouldWrap()
    {
        $eventTranslator = new StubEventTranslator();
        $numMessages = $this->ringBuffer->getBufferSize();
        $offset = 1000;

        for ($i = 0; $i < $numMessages + $offset; $i++) {
            $args = new StackableArray();
            $args[0] = $i;
            $args[1] = '';
            $this->ringBuffer->publishEvent($eventTranslator, $args);
        }

        $expectedSequence = $numMessages + $offset - 1;
        $available = $this->sequenceBarrier->waitFor($expectedSequence);
        $this->assertEquals($expectedSequence, $available);

        for ($i = $offset; $i < $numMessages + $offset; $i++) {
            $value = $this->ringBuffer->get($i)->getValue();
            $this->assertEquals($i, $value);
        }
    }

    public function testShouldPreventWrapping()
    {
        $eventFactory = new StubEventFactory();
        $eventTranslator = new StubEventTranslator();

        $sequence = new Sequence();
        $sequences = new StackableArray();
        $sequences[] = $sequence;
        $ringBuffer = RingBuffer::createMultiProducer($eventFactory, 4);
        $ringBuffer->addGatingSequences($sequences);

        $arg0 = new StackableArray();
        $arg0[] = 0;
        $arg0[] = 0;
        $ringBuffer->publishEvent($eventTranslator, $arg0);
        $arg1 = new StackableArray();
        $arg1[] = 1;
        $arg1[] = 1;
        $ringBuffer->publishEvent($eventTranslator, $arg1);
        $arg2 = new StackableArray();
        $arg2[] = 2;
        $arg2[] = 2;
        $ringBuffer->publishEvent($eventTranslator, $arg2);
        $arg3 = new StackableArray();
        $arg3[] = 3;
        $arg3[] = 3;
        $ringBuffer->publishEvent($eventTranslator, $arg3);

        $this->assertFalse($ringBuffer->tryPublishEvent($eventTranslator, $arg3));
    }

    public function testShouldThrowExceptionIfBufferIsFull()
    {
        $sequences = new StackableArray();
        $sequences[] = new Sequence($this->ringBuffer->getBufferSize());

        $this->ringBuffer->addGatingSequences($sequences);

        try {
            for ($i = 0; $i < $this->ringBuffer->getBufferSize(); $i++) {
                $this->ringBuffer->publish($this->ringBuffer->tryNext());
            }
        } catch (\Exception $e) {
            $this->fail('Should not of thrown exception');
        }

        try {
            $this->ringBuffer->tryNext();
            $this->fail('Exception should have been thrown');
        } catch (InsufficientCapacityException $e) {
        }
    }

    public function testShouldHandleResetToAndNotWrapUnecessarilySingleProducer()
    {
        $eventFactory = new StubEventFactory();
        $ringBuffer = RingBuffer::createSingleProducer($eventFactory, 4);
        $this->assertHandleResetAndNotWrap($ringBuffer);
    }

    public function testShouldHandleResetToAndNotWrapUnecessarilyMultiProducer()
    {
        $eventFactory = new StubEventFactory();
        $ringBuffer = RingBuffer::createMultiProducer($eventFactory, 4);
        $this->assertHandleResetAndNotWrap($ringBuffer);
    }

    private function assertHandleResetAndNotWrap(RingBuffer $rb)
    {
        $sequences = new StackableArray();
        $sequence = new Sequence();
        $sequences[] = $sequence;
        $rb->addGatingSequences($sequences);

        for ($i = 0; $i < 128; $i++) {
            $rb->publish($rb->next());
            $sequence->incrementAndGet();
        }

        $this->assertEquals(127, $rb->getCursor());

        $rb->resetTo(31);
        $sequence->set(31);

        for ($i = 0; $i < 4; $i++) {
            $rb->publish($rb->next());
        }

        $this->assertFalse($rb->hasAvailableCapacity(1));
    }
}
