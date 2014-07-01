<?php

namespace PhpDisruptorTest\Dsl\Disruptor;

use PhpDisruptor\Dsl\Disruptor;
use PhpDisruptor\Dsl\ProducerType;
use PhpDisruptor\Lists\EventHandlerList;
use PhpDisruptor\Lists\WorkHandlerList;
use PhpDisruptor\RingBuffer;
use PhpDisruptorTest\TestAsset\SleepingEventHandler;
use PhpDisruptorTest\TestAsset\TestEvent;
use PhpDisruptorTest\TestAsset\TestEventFactory;

class DisruptorTest extends \PHPUnit_Framework_TestCase
{
    CONST TIMEOUT_IN_SECONDS = 2;

    /**
     * @var Disruptor
     */
    private $disruptor;

    /**
     * @var EventHandlerList
     */
    private $delayedEventHandlers;

    /**
     * @var WorkHandlerList
     */
    private $testWorkHandlers;

    /**
     * @var RingBuffer
     */
    private $ringBuffer;

    /**
     * @var TestEvent
     */
    private $lastPublishedEvent;

    /**
     * @var string
     */
    private $eventClass;

    protected function setUp()
    {
        $eventFactory = new TestEventFactory();
        $this->eventClass = $eventFactory->getEventClass();
        $producerType = ProducerType::SINGLE();
        $this->disruptor = new Disruptor($eventFactory, 4, $producerType);
        $this->delayedEventHandlers = new EventHandlerList();
        $this->testWorkHandlers = new WorkHandlerList();
    }

    protected function tearDown()
    {
        foreach ($this->delayedEventHandlers as $delayedEventHandler) {
            $delayedEventHandler->stopWaiting();
        }

        foreach ($this->testWorkHandlers as $testWorkHandler) {
            $testWorkHandler->stopWaiting();
        }

        $this->disruptor->halt();
    }

    public function testShouldCreateEventProcessorGroupForFirstEventProcessors()
    {
        $eventHandler1 = new SleepingEventHandler($this->eventClass);
        $eventHandler2 = new SleepingEventHandler($this->eventClass);

        $eventHandlerList  = new EventHandlerList(array($eventHandler1, $eventHandler2));

        $eventHandlerGroup = $this->disruptor->handleEventsWithEventHandlers($eventHandlerList);
        $this->disruptor->start();

        $this->assertNotNull($eventHandlerGroup);
    }
}
