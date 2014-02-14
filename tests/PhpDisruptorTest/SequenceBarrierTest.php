<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\Lists\EventProcessorList;
use PhpDisruptor\Lists\SequenceList;
use ConcurrentPhpUtils\CountDownLatch;
use ConcurrentPhpUtils\NoOpStackable;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\Util\Util;
use PhpDisruptorTest\TestAsset\CountDownLatchSequence;
use PhpDisruptorTest\TestAsset\RingBufferThread;
use PhpDisruptorTest\TestAsset\SequenceBarrierThread;
use PhpDisruptorTest\TestAsset\StubEventProcessor;
use PhpDisruptorTest\TestAsset\StubEventFactory;
use PhpDisruptorTest\TestAsset\StubEventProcessorThread;

class SequenceBarrierTest extends \PHPUnit_Framework_TestCase
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
        $this->eventFactory = new StubEventFactory();
        $this->eventProcessor1 = new StubEventProcessor();
        $this->eventProcessor2 = new StubEventProcessor();
        $this->eventProcessor3 = new StubEventProcessor();
        $this->ringBuffer = RingBuffer::createMultiProducer($this->eventFactory, 64);
        $eventProcessor = new NoOpEventProcessor($this->ringBuffer);
        $sequences = new SequenceList($eventProcessor->getSequence());
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

        $seqs = array(
            $this->eventProcessor1->getSequence(),
            $this->eventProcessor2->getSequence(),
            $this->eventProcessor3->getSequence()
        );
        $sequences = new SequenceList($seqs);

        $sequenceBarrier = $this->ringBuffer->newBarrier($sequences);

        $completedWorkSequence = $sequenceBarrier->waitFor($expectedWorkSequence);
        $this->assertTrue($completedWorkSequence >= $expectedWorkSequence);
    }

    public function testShouldWaitForWorkCompleteWhereAllWorkersAreBlockedOnRingBuffer()
    {
        $expectedNumberMessages = 10;
        $this->fillRingBuffer($expectedNumberMessages);

        $workers = new EventProcessorList();
        for ($i = 0; $i < 3; $i++) {
            $worker = new StubEventProcessor();
            $worker->setSequence($expectedNumberMessages - 1);
            $workers->add($worker);
        }

        $sequenceBarrier = $this->ringBuffer->newBarrier(Util::getSequencesFor($workers));
        $thread = new RingBufferThread($this->ringBuffer, $workers);
        $thread->start();
        $thread->join();

        $expectedWorkSequence = $expectedNumberMessages;
        $completedWorkSequence = $sequenceBarrier->waitFor($expectedNumberMessages);
        $this->assertTrue($completedWorkSequence >= $expectedWorkSequence);
    }

    public function testShouldInterruptDuringBusySpin()
    {
        $expectedNumberMessages = 10;
        $this->fillRingBuffer($expectedNumberMessages);

        $countDownLatch = new CountDownLatch(3);
        $sequence1 = new CountDownLatchSequence(8, $countDownLatch);
        $sequence2 = new CountDownLatchSequence(8, $countDownLatch);
        $sequence3 = new CountDownLatchSequence(8, $countDownLatch);

        $this->eventProcessor1->setSequenceObject($sequence1);
        $this->eventProcessor2->setSequenceObject($sequence2);
        $this->eventProcessor3->setSequenceObject($sequence3);

        $processors = new EventProcessorList();
        $processors->add($this->eventProcessor1);
        $processors->add($this->eventProcessor2);
        $processors->add($this->eventProcessor3);

        $sequencesToTrack = Util::getSequencesFor($processors);
        $sequenceBarrier = $this->ringBuffer->newBarrier($sequencesToTrack);

        $alerted = new NoOpStackable();
        $alerted[0] = false;

        $thread = new SequenceBarrierThread($sequenceBarrier, $expectedNumberMessages, $alerted);
        $thread->start();
        $countDownLatch->await(3000000); // 3sec
        $sequenceBarrier->alert();
        $thread->join();

        $this->assertTrue($alerted[0], 'Thread was not interrupted');
    }

    public function testShouldWaitForWorkCompleteWhereCompleteWorkThresholdIsBehind()
    {
        $expectedNumberMessages = 10;
        $this->fillRingBuffer($expectedNumberMessages);

        $eventProcessors = new EventProcessorList();
        for ($i = 0; $i < 3; $i++) {
            $eventProcessors[$i] = new StubEventProcessor();
            $eventProcessors[$i]->setSequence($expectedNumberMessages - 2);
        }

        $sequenceBarrier = $this->ringBuffer->newBarrier(Util::getSequencesFor($eventProcessors));

        $thread = new StubEventProcessorThread($eventProcessors);
        $thread->start();
        $thread->join();

        $expectedWorkSequence = $expectedNumberMessages - 1;
        $completedWorkSequence = $sequenceBarrier->waitFor($expectedWorkSequence);
        $this->assertTrue($completedWorkSequence >= $expectedWorkSequence);
    }

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
