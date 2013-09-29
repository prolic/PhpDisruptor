<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\Util\Util;
use PhpDisruptorTest\TestAsset\RingBufferThread;
use PhpDisruptorTest\TestAsset\SequenceBarrierThread;
use PhpDisruptorTest\TestAsset\StubEventProcessor;
use PhpDisruptorTest\TestAsset\StubEventFactory;

class SequenceBarrierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RingBuffer
     */
    protected $ringBuffer;

    /**
     * @var StubEventProcessor
     */
    protected $eventProcessor1;

    /**
     * @var StubEventProcessor
     */
    protected $eventProcessor2;

    /**
     * @var StubEventProcessor
     */
    protected $eventProcessor3;

    protected function setUp()
    {
        $this->eventProcessor1 = new StubEventProcessor();
        $this->eventProcessor2 = new StubEventProcessor();
        $this->eventProcessor3 = new StubEventProcessor();
        $eventFactory = new StubEventFactory();
        $this->ringBuffer = RingBuffer::createMultiProducer($eventFactory, 64);
        $eventProcessor = new NoOpEventProcessor($this->ringBuffer);
        $sequences = new StackableArray();
        $sequences[] = $eventProcessor->getSequence();
        $this->ringBuffer->addGatingSequences($sequences);
    }

    public function testShouldWaitForWorkCompleteWhereCompleteWorkThresholdIsAhead()
    {
        $expectedNumberMessages = 10;
        $expectedWorkSequence = 9;
        $this->fillRingBuffer($expectedNumberMessages);

        $this->eventProcessor1->setSequence($expectedNumberMessages);
        $this->eventProcessor2->setSequence($expectedWorkSequence);
        $this->eventProcessor3->setSequence($expectedNumberMessages);

        $sequences = new StackableArray();
        $sequences[] = $this->eventProcessor1->getSequence();
        $sequences[] = $this->eventProcessor2->getSequence();
        $sequences[] = $this->eventProcessor3->getSequence();

        $sequenceBarrier = $this->ringBuffer->newBarrier($sequences);

        $completedWorkSequence = $sequenceBarrier->waitFor($expectedWorkSequence);
        $this->assertTrue($completedWorkSequence >= $expectedWorkSequence);
    }

    /**
     * @todo: tests fails without wait() call on the thread
     */
    public function testShouldWaitForWorkCompleteWhereAllWorkersAreBlockedOnRingBuffer()
    {
        $expectedNumberMessages = 10;
        $this->fillRingBuffer($expectedNumberMessages);

        $workers = new StackableArray();
        for ($i = 0; $i < 3; $i++) {
            $worker = $workers[$i] = new StubEventProcessor();
            $worker->setSequence($expectedNumberMessages - 1);
        }

        $sequenceBarrier = $this->ringBuffer->newBarrier(Util::getSequencesFor($workers));
        $thread = new RingBufferThread($this->ringBuffer, $workers);
        $thread->start();
        $thread->wait(); // dirty hack
        $expectedWorkSequence = $expectedNumberMessages;
        $completedWorkSequence = $sequenceBarrier->waitFor($expectedNumberMessages);
        $this->assertTrue($completedWorkSequence >= $expectedWorkSequence);
    }

    /**
     * @todo: failing !!!
     */
//    public function testShouldInterruptDuringBusySpin()
//    {
//        $expectedNumberMessages = 10;
//        $this->fillRingBuffer($expectedNumberMessages);
//
//        $sequence1 = new Sequence(8);
//        $sequence2 = new Sequence(8);
//        $sequence3 = new Sequence(8);
//
//        $this->eventProcessor1->setSequence($sequence1->get());
//        $this->eventProcessor2->setSequence($sequence2->get());
//        $this->eventProcessor3->setSequence($sequence3->get());
//
//        $processors = new StackableArray();
//        $processors[] = $this->eventProcessor1;
//        $processors[] = $this->eventProcessor2;
//        $processors[] = $this->eventProcessor3;
//
//        $sequenceBarrier = $this->ringBuffer->newBarrier(
//            Util::getSequencesFor($processors)
//        );
//
//        $alerted = new StackableArray();
//        $alerted[0] = false;
//
//        var_dump(get_class($this));
//        var_dump(gettype($this));
//
//        $thread = new SequenceBarrierThread($sequenceBarrier, $expectedNumberMessages, $alerted);
//        $thread->start();
//
//        var_dump(get_class($this));
//        var_dump(gettype($this));
//        die;
//        sleep(3);
//
//        $thread->join();
//
//        $this->assertTrue($alerted[0], 'Thread was not interrupted');
//    }

    public function testShouldSetAndClearAlertStatus()
    {
        $sequenceBarrier = $this->ringBuffer->newBarrier();
        $this->assertFalse($sequenceBarrier->isAlerted());

        $sequenceBarrier->alert();
        $this->assertTrue($sequenceBarrier->isAlerted());

        $sequenceBarrier->clearAlert();
        $this->assertFalse($sequenceBarrier->isAlerted());
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
