<?php

namespace PhpDisruptor\Pthreads;

use PhpDisruptor\Pthreads\TimeUnit;

class TimeUnitTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        foreach (TimeUnit::getConstants() as $u) {
            $u = TimeUnit::get($u);
            $this->assertInstanceOf('PhpDisruptor\Pthreads\TimeUnit', $u);
            $this->assertEquals(42, $u->convert(42, $u));
            foreach (TimeUnit::getConstants() as $v) {
                $v = TimeUnit::get($v);
                if ($v->convert(42, $v) >= 42) {
                    $this->assertEquals(42, $v->convert($u->convert(42, $v), $u));
                }
            }
        }

        $this->assertEquals(24, TimeUnit::HOURS()->convert(1, TimeUnit::DAYS()));
        $this->assertEquals(60, TimeUnit::MINUTES()->convert(1, TimeUnit::HOURS()));
        $this->assertEquals(60, TimeUnit::SECONDS()->convert(1, TimeUnit::MINUTES()));
        $this->assertEquals(1000, TimeUnit::MILLISECONDS()->convert(1, TimeUnit::SECONDS()));
        $this->assertEquals(1000, TimeUnit::MICROSECONDS()->convert(1, TimeUnit::MILLISECONDS()));
        $this->assertEquals(1000, TimeUnit::NANOSECONDS()->convert(1, TimeUnit::MICROSECONDS()));

        $this->assertEquals(24, TimeUnit::DAYS()->toHours(1));
        $this->assertEquals(60, TimeUnit::HOURS()->toMinutes(1));
        $this->assertEquals(60, TimeUnit::MINUTES()->toSeconds(1));
        $this->assertEquals(1000, TimeUnit::SECONDS()->toMillis(1));
        $this->assertEquals(1000, TimeUnit::MILLISECONDS()->toMicros(1));
        $this->assertEquals(1000, TimeUnit::MICROSECONDS()->toNanos(1));
    }
}
