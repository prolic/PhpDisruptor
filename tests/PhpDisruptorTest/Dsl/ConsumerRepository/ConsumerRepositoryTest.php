<?php

namespace PhpDisruptorTest\Dsl\ConsumerRepository;

use PhpDisruptor\Dsl\ConsumerRepository;
use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use ConcurrentPhpUtils\NoOpStackable;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\Dsl\ConsumerRepository\TestAsset\SleepingEventHandler;
use PhpDisruptorTest\TestAsset\TestEventFactory;
use PhpDisruptorTest\TestAsset\TestEventProcessor;

class ConsumerRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventFactoryInterface
     */
    protected $eventFactory;

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
        $this->eventFactory = new TestEventFactory();
        $this->consumerRepository = new ConsumerRepository($this->eventFactory);

        $sequence1 = new Sequence();
        $sequence2 = new Sequence();
        $this->eventProcessor1 = new TestEventProcessor($sequence1);
        $this->eventProcessor2 = new TestEventProcessor($sequence2);

        $this->handler1 = new SleepingEventHandler($this->eventFactory->getEventClass());
        $this->handler2 = new SleepingEventHandler($this->eventFactory->getEventClass());

        $ringBuffer = RingBuffer::createMultiProducer($this->eventFactory, 64);

        $this->barrier1 = $ringBuffer->newBarrier();
        $this->barrier2 = $ringBuffer->newBarrier();
    }

    public function testShouldGetBarrierByHandler()
    {
        $this->consumerRepository->addEventProcessor($this->eventProcessor1, $this->handler1, $this->barrier1);
        $this->assertTrue($this->barrier1->equals($this->consumerRepository->getBarrierFor($this->handler1)));
    }

    public function testShouldReturnNullForBarrierWhenHandlerIsNotRegistered()
    {
        $this->assertNull($this->consumerRepository->getBarrierFor($this->handler1));
    }

    public function testShouldGetLastEventProcessorsInChain()
    {
        $this->consumerRepository->addEventProcessor($this->eventProcessor1, $this->handler1, $this->barrier1);
        $this->consumerRepository->addEventProcessor($this->eventProcessor2, $this->handler2, $this->barrier2);

        $data = new NoOpStackable();
        $data[] = $this->eventProcessor2->getSequence();
        $this->consumerRepository->unMarkEventProcessorsAsEndOfChain($data);

        $lastEventProcessorsInChain = $this->consumerRepository->getLastSequenceInChain(true);
        $this->assertEquals(1, count($lastEventProcessorsInChain));
        $this->assertTrue($this->eventProcessor1->getSequence()->equals($lastEventProcessorsInChain[0]));
    }

    public function testShouldRetrieveEventProcessorForHandler()
    {
        $this->consumerRepository->addEventProcessor($this->eventProcessor1, $this->handler1, $this->barrier1);
        $this->assertTrue($this->consumerRepository->getEventProcessorFor($this->handler1)->equals($this->eventProcessor1));
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     * @expectedExceptionMessage The given event handler is not processing events
     */
    public function testShouldThrowExceptionWhenHandlerIsNotRegistered()
    {
        $eventFactory = new TestEventFactory();
        $eventHandler = new SleepingEventHandler($eventFactory->getEventClass());
        $this->consumerRepository->getEventProcessorFor($eventHandler);
    }

    public function testShouldIterateAllEventProcessors()
    {
        $this->consumerRepository->addEventProcessor($this->eventProcessor1, $this->handler1, $this->barrier1);
        $this->consumerRepository->addEventProcessor($this->eventProcessor2, $this->handler2, $this->barrier2);

        $seen1 = false;
        $seen2 = false;

        foreach($this->consumerRepository->getConsumerInfos() as $info) {
            if (!$seen1
                && $info->getEventProcessor()->equals($this->eventProcessor1)
                && $info->getHandler()->equals($this->handler1)
            ) {
                $seen1 = true;
            } else if (!$seen2
                && $info->getEventProcessor()->equals($this->eventProcessor2)
                && $info->getHandler()->equals($this->handler2)
            ) {
                $seen2  = true;
            } else {
                $this->fail('Unexpected event processor info');
            }
        }

        $this->assertTrue($seen1);
        $this->assertTrue($seen2);
    }
}
