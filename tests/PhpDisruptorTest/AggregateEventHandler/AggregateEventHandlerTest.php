<?php

namespace PhpDisruptorTest;

use PhpDisruptor\AggregateEventHandler;
use PhpDisruptor\Lists\EventHandlerList;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptorTest\TestAsset\EventHandler;
use PHPUnit_Framework_MockObject_MockObject;

class AggregateEventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventHandlerOne;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventHandlerTwo;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventHandlerThree;

    protected function setUp()
    {
        $this->eventHandlerOne = new EventHandler('stdClass');
        $this->eventHandlerTwo = new EventHandler('stdClass', 2);
        $this->eventHandlerThree = new EventHandler('stdClass', 3);
    }

    public function testShouldCallOnEventInSequence()
    {
        $event = new \stdClass();
        $sequence = 3;
        $endOfBatch = true;

        $aggregateEventHandler = $this->prepareAggregateEventHandler();
        ob_start();
        $aggregateEventHandler->onEvent($event, $sequence, $endOfBatch);
        $result = ob_get_clean();
        $this->assertEquals('123', $result);
    }

    public function testShouldCallOnStartInSequence()
    {
        $aggregateEventHandler = $this->prepareAggregateEventHandler();
        ob_start();
        $aggregateEventHandler->onShutdown();
        $result = ob_get_clean();
        $this->assertEquals('123', $result);
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

    public function prepareAggregateEventHandler()
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
