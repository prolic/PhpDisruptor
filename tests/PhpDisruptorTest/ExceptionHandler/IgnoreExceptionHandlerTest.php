<?php

namespace PhpDisruptorTest\ExceptionHandler;

use PhpDisruptor\ExceptionHandler\IgnoreExceptionHandler;
use PhpDisruptorTest\TestAsset\TestEvent;

class IgnoreExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldHandleAndIgnoreException()
    {
        if (file_exists(sys_get_temp_dir() . '/fatallog')) {
            unlink(sys_get_temp_dir() . '/fatallog');
        }

        $ex = new \Exception();
        $event = new TestEvent();
        $handler = new IgnoreExceptionHandler(sys_get_temp_dir() . '/fatallog');

        $handler->handleEventException($ex, 0, $event);

        $res = file_get_contents(sys_get_temp_dir() . '/fatallog');
        $this->assertEquals('INFO: Exception processing: 0 Test Event', $res);

        if (file_exists(sys_get_temp_dir() . '/fatallog')) {
            unlink(sys_get_temp_dir() . '/fatallog');
        }
    }
}
