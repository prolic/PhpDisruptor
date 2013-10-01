<?php

namespace PhpDisruptorTest\Dsl;

use PhpDisruptor\Dsl\ConsumerRepository;
use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\ProcessingSequenceBarrier;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\TestAsset\SleepingEventHandler;
use PhpDisruptorTest\TestAsset\TestEventFactory;
use PhpDisruptorTest\TestAsset\TestEventProcessor;

class ConsumerRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConsumerRepository
     */
    protected $consumerRepository;

    /**
     * @var AbstractEventProcessor
     */
    protected $eventProcessor1;

    /**
     * @var AbstractEventProcessor
     */
    protected $eventProcessor2;

    /**
     * @var SleepingEventHandler
     */
    protected $handler1;

    /**
     * @var SleepingEventHandler
     */
    protected $handler2;

    /**
     * @var SequenceBarrierInterface
     */
    protected $barrier1;

    /**
     * @var SequenceBarrierInterface
     */
    protected $barrier2;


    protected function setUp()
    {
        $eventFactory = new TestEventFactory();
        $this->consumerRepository = new ConsumerRepository($eventFactory);

        $sequence1 = new Sequence();
        $sequence2 = new Sequence();
        $this->eventProcessor1 = new TestEventProcessor($sequence1);
        $this->eventProcessor2 = new TestEventProcessor($sequence2);

        $this->handler1 = new SleepingEventHandler($eventFactory->getEventClass());
        $this->handler2 = new SleepingEventHandler($eventFactory->getEventClass());

        $ringBuffer = RingBuffer::createMultiProducer($eventFactory, 64);

        $this->barrier1 = $ringBuffer->newBarrier();
        $this->barrier2 = $ringBuffer->newBarrier();
    }

    public function testShouldGetBarrierByHandler()
    {
        $this->consumerRepository->addEventProcessor($this->eventProcessor1, $this->handler1, $this->barrier1);
        $this->assertTrue($this->barrier1->equals($this->consumerRepository->getBarrierFor($this->handler1)));
    }
}
