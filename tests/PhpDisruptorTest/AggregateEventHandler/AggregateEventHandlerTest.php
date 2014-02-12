<?php

namespace PhpDisruptorTest\AggregateEventHandler;

use PhpDisruptor\AggregateEventHandler;
use PhpDisruptor\Lists\EventHandlerList;
use PhpDisruptorTest\AggregateEventHandler\TestAsset\EventHandler;
use PhpDisruptorTest\AggregateEventHandler\TestAsset\ResultCounter;
use PHPUnit_Framework_MockObject_MockObject;

class AggregateEventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $eventHandlerOne;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $eventHandlerTwo;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $eventHandlerThree;

    /**
     * @var ResultCounter
     */
    private $result;

    protected function setUp()
    {
        $this->result = $result = new ResultCounter();
        $this->eventHandlerOne = new EventHandler('stdClass', $result);
        $this->eventHandlerTwo = new EventHandler('stdClass', $result);
        $this->eventHandlerThree = new EventHandler('stdClass', $result);
    }

    public function testShouldCallOnEventInSequence()
    {
        $event = new \stdClass();
        $sequence = 3;
        $endOfBatch = true;

        $aggregateEventHandler = $this->prepareAggregateEventHandler();

        $aggregateEventHandler->onEvent($event, $sequence, $endOfBatch);

        $this->assertEquals('123', $this->result->getResult());
    }

    public function testShouldCallOnStartInSequence()
    {
        $aggregateEventHandler = $this->prepareAggregateEventHandler();

        $aggregateEventHandler->onShutdown();

        $this->assertEquals('123', $this->result->getResult());
    }

    public function testShouldHandleEmptyListOfEventHandlers()
    {
        $handlers = new EventHandlerList();
        $aggregateEventHandler = new AggregateEventHandler('stdClass', $handlers);
        $event = new \stdClass();
        $aggregateEventHandler->onEvent($event, 0, true);
        $aggregateEventHandler->onStart();
        $aggregateEventHandler->onShutdown();
    }

    private function prepareAggregateEventHandler()
    {
        $handlers = new EventHandlerList(array(
            $this->eventHandlerOne,
            $this->eventHandlerTwo,
            $this->eventHandlerThree
        ));

        $aggregateEventHandler = new AggregateEventHandler(
            'stdClass',
            $handlers
        );

        return $aggregateEventHandler;
    }
}
