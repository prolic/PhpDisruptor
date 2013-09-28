<?php

namespace PhpDisruptorTest;

use PhpDisruptor\AggregateEventHandler;
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
        $this->eventHandlerOne = new EventHandler('stdClass', 1);
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
        $aggregateEventHandler = new AggregateEventHandler('stdClass', new StackableArray());
        $aggregateEventHandler->onEvent(new \stdClass(), 0, true);
        $aggregateEventHandler->onStart();
        $aggregateEventHandler->onShutdown();
    }

    public function prepareAggregateEventHandler()
    {
        $handlers = new StackableArray();
        $handlers[] = $this->eventHandlerOne;
        $handlers[] = $this->eventHandlerTwo;
        $handlers[] = $this->eventHandlerThree;

        $aggregateEventHandler = new AggregateEventHandler(
            'stdClass',
            $handlers
        );

        return $aggregateEventHandler;
    }
}
