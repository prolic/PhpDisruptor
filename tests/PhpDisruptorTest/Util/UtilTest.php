<?php

namespace PhpDisruptorTest\Util;

use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Sequence;
use PhpDisruptor\Util\Util;
use PHPUnit_Framework_TestCase as TestCase;

class UtilTest extends TestCase
{
    public function testShouldReturnNextPowerOfTwo()
    {
        $powerOfTwo = Util::ceilingNextPowerOfTwo(1000);
        $this->assertEquals(1024, $powerOfTwo);
    }

    public function testShouldReturnExactPowerOfTwo()
    {
        $powerOfTwo = Util::ceilingNextPowerOfTwo(1024);
        $this->assertEquals(1024, $powerOfTwo);
    }

    public function testLog2Of23()
    {
        $log2 = Util::log2(23);
        $this->assertEquals(4, $log2);
    }

    public function testLog2Of1000()
    {
        $log2 = Util::log2(1000);
        $this->assertEquals(9, $log2);
    }

    public function dataProvider()
    {
        $one = new StackableArray();
        $two = new StackableArray();
        $three = new StackableArray();

        $one[] = new Sequence(3);
        $one[] = new Sequence(5);
        $one[] = new Sequence(7);

        $two[] = new Sequence(7);
        $two[] = new Sequence(5);
        $two[] = new Sequence(3);

        $three[] = new Sequence(5);
        $three[] = new Sequence(7);
        $three[] = new Sequence(3);

        return array(
            array(
                $one
            ),
            array(
                $two
            ),
            array(
                $three
            )
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldReturnMinimumSequence($data)
    {
        $this->assertEquals(3, Util::getMinimumSequence($data));
    }

    public function testShouldReturnPhpIntMaxWhenNoEventProcessors()
    {
        $sequences = new StackableArray();
        $this->assertEquals(PHP_INT_MAX, Util::getMinimumSequence($sequences));
    }
}
