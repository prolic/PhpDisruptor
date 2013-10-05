<?php

namespace PhpDisruptorTest\ExceptionHandler;

use PhpDisruptor\Exception\RuntimeException;
use PhpDisruptor\ExceptionHandler\FatalExceptionHandler;
use PhpDisruptorTest\TestAsset\TestEvent;

class FatalExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldHandleFatalException()
    {
        if (file_exists(sys_get_temp_dir() . '/fatallog')) {
            unlink(sys_get_temp_dir() . '/fatallog');
        }

        $ex = new \Exception();
        $event = new TestEvent();
        $handler = new FatalExceptionHandler(sys_get_temp_dir() . '/fatallog');

        try {
            $handler->handleEventException($ex, 0, $event);
        } catch (RuntimeException $e) {
            $this->assertEquals($ex, $e->getPrevious());
        }

        $res = file_get_contents(sys_get_temp_dir() . '/fatallog');
        $this->assertEquals('ERR: Exception processing: 0 Test Event', $res);

        if (file_exists(sys_get_temp_dir() . '/fatallog')) {
            unlink(sys_get_temp_dir() . '/fatallog');
        }
    }
}
