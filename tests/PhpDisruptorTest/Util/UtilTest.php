<?php

namespace PhpDisruptorTest\Util;

use PhpDisruptor\Sequence;
use PhpDisruptor\Util\Util;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Cache\Storage\Adapter\Memory as Storage;

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

    public function testShouldReturnMinimumSequence()
    {
        $storage = new Storage();
        $sequences = array();
        $sequences[0] = new Sequence($storage, 3);
        $sequences[1] = new Sequence($storage, 5);
        $sequences[2] = new Sequence($storage, 7);

        $this->assertEquals(3, Util::getMinimumSequence($sequences));
    }

    public function testShouldReturnPhpIntMaxWhenNoEventProcessors()
    {
        $sequences = array();
        $this->assertEquals(PHP_INT_MAX, Util::getMinimumSequence($sequences));
    }
}
