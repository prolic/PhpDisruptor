<?php

namespace PhpDisruptorTest;

use PhpDisruptor\AggregateEventHandler;
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
        $this->eventHandlerOne   = $this->getMock(
            'PhpDisruptorTest\TestAsset\LifecycleAwareEventHandler',
            array(
                'onEvent',
                'onShutdown'
            ),
            array(
                'stdClass'
            )
        );
        $this->eventHandlerTwo   = $this->getMock(
            'PhpDisruptorTest\TestAsset\LifecycleAwareEventHandler',
            array(
                'onEvent',
                'onShutdown'
            ),
            array(
                'stdClass'
            )
        );
        $this->eventHandlerThree = $this->getMock(
            'PhpDisruptorTest\TestAsset\LifecycleAwareEventHandler',
            array(
                'onEvent',
                'onShutdown'
            ),
            array(
                'stdClass'
            )
        );
    }

    public function testShouldCallOnEventInSequence()
    {
        $event = new \stdClass();
        $sequence = 3;
        $endOfBatch = true;

        $this->eventHandlerOne->expects($this->once())->method('onEvent')->will($this->returnCallback(
            function() { echo '1'; }
        ));
        $this->eventHandlerTwo->expects($this->once())->method('onEvent')->will($this->returnCallback(
            function() { echo '2'; }
        ));
        $this->eventHandlerThree->expects($this->once())->method('onEvent')->will($this->returnCallback(
            function() { echo '3'; }
        ));

        $aggregateEventHandler = new AggregateEventHandler(
            'stdClass',
            array(
                $this->eventHandlerOne,
                $this->eventHandlerTwo,
                $this->eventHandlerThree
            )
        );

        ob_start();
        $aggregateEventHandler->onEvent($event, $sequence, $endOfBatch);
        $result = ob_get_clean();
        $this->assertEquals('123', $result);
    }

    public function testShouldCallOnStartInSequence()
    {
        $this->eventHandlerOne->expects($this->once())->method('onShutdown')->will($this->returnCallback(
            function() { echo '1'; }
        ));
        $this->eventHandlerTwo->expects($this->once())->method('onShutdown')->will($this->returnCallback(
            function() { echo '2'; }
        ));
        $this->eventHandlerThree->expects($this->once())->method('onShutdown')->will($this->returnCallback(
            function() { echo '3'; }
        ));

        $aggregateEventHandler = new AggregateEventHandler(
            'stdClass',
            array(
                $this->eventHandlerOne,
                $this->eventHandlerTwo,
                $this->eventHandlerThree
            )
        );

        ob_start();
        $aggregateEventHandler->onShutdown();
        $result = ob_get_clean();
        $this->assertEquals('123', $result);
    }

    public function testShouldHandleEmptyListOfEventHandlers()
    {
        $aggregateEventHandler = new AggregateEventHandler('stdClass', array());
        $aggregateEventHandler->onEvent(new \stdClass(), 0, true);
        $aggregateEventHandler->onStart();
        $aggregateEventHandler->onShutdown();
    }
}
