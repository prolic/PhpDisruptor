<?php

namespace PhpDisruptorTest;

use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Sequence;
use PhpDisruptor\FixedSequenceGroup;
use PHPUnit_Framework_TestCase;

class FixedSequenceGroupTest extends PHPUnit_Framework_TestCase
{
    public function testShouldReturnMinimumOf2Sequences()
    {
        $sequence1 = new Sequence(34);
        $sequence2 = new Sequence(47);
        $sequences = new StackableArray();
        $sequences[] = $sequence1;
        $sequences[] = $sequence2;
        $sequenceGroup = new FixedSequenceGroup($sequences);

        $this->assertEquals(34, $sequenceGroup->get());
        $sequence1->set(35);
        $this->assertEquals(35, $sequenceGroup->get());
        $sequence1->set(48);
        $this->assertEquals(47, $sequenceGroup->get());
    }
}
