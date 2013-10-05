<?php

namespace PhpDisruptorTest;

use PhpDisruptorTest\TestAsset\ExampleEventTranslator;
use PhpDisruptorTest\TestAsset\StubEventFactory;

class EventTranslatorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_VALUE = "Wibble";

    public function testShouldTranslateOtherDataIntoAnEvent()
    {
        $eventFactory = new StubEventFactory();
        $event = $eventFactory->newInstance();

        $eventTranslator = new ExampleEventTranslator(self::TEST_VALUE);
        $eventTranslator->translateTo($event, 0);

        $this->assertEquals(self::TEST_VALUE, $event->getTestString());
    }
}
