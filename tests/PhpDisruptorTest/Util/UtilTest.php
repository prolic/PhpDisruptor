<?php

namespace PhpDisruptorTest\Util;

use PhpDisruptor\Lists\SequenceList;
use PhpDisruptor\Sequence;
use PhpDisruptor\Util\Util;
use PHPUnit_Framework_TestCase as TestCase;
use Threaded;

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
        $sequences1 = array(
            new Sequence(3),
            new Sequence(5),
            new Sequence(7)
        );
        $one = new SequenceList($sequences1);

        $sequences2 = array(
            new Sequence(7),
            new Sequence(5),
            new Sequence(3)
        );
        $two = new SequenceList($sequences2);

        $sequences3 = array(
            new Sequence(5),
            new Sequence(7),
            new Sequence(3)
        );
        $three = new SequenceList($sequences3);

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
        $sequences = new SequenceList();
        $this->assertEquals(PHP_INT_MAX, Util::getMinimumSequence($sequences));
    }
}
