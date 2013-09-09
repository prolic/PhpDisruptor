<?php

namespace PhpDisruptorTest;

use PhpDisruptor\Sequence;
use PhpDisruptor\FixedSequenceGroup;
use PHPUnit_Framework_TestCase;

class FixedSequenceGroupTest extends PHPUnit_Framework_TestCase
{
    public function testShouldReturnMinimumOf2Sequences()
    {
        $storage = new \Zend\Cache\Storage\Adapter\Memory();
        $sequence1 = new Sequence($storage, 34);
        $sequence2 = new Sequence($storage, 47);
        $sequenceGroup = new FixedSequenceGroup(array($sequence1, $sequence2));

        $this->assertEquals(34, $sequenceGroup->get());
        $sequence1->set(35);
        $this->assertEquals(35, $sequenceGroup->get());
        $sequence1->set(48);
        $this->assertEquals(47, $sequenceGroup->get());
    }
}
