<?php

namespace PhpDisruptorTest;

use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceGroup;
use PhpDisruptorTest\TestAsset\TestEventFactory;
use PHPUnit_Framework_TestCase as TestCase;

class SequenceGroupTest extends TestCase
{
    public function testShouldReturnMaxSequenceWhenEmptyGroup()
    {
        $sequenceGroup = new SequenceGroup();
        $result = $sequenceGroup->get();
        $this->assertEquals(PHP_INT_MAX, $result);
    }

    public function testShouldAddOneSequenceToGroup()
    {
        $sequence = new Sequence(7);
        $sequenceGroup = new SequenceGroup();
        $sequenceGroup->add($sequence);

        $this->assertEquals($sequence->get(), $sequenceGroup->get());
    }

    public function testShouldNotFailIfTryingToRemoveNotExistingSequence()
    {
        $sequenceGroup = new SequenceGroup();
        $sequence1 = new Sequence();
        $sequence2 = new Sequence();
        $sequence3 = new Sequence();
        $sequenceGroup->add($sequence1);
        $sequenceGroup->add($sequence2);
        $sequenceGroup->remove($sequence3);
    }

    public function testShouldReportTheMinimumSequenceForGroupOfTwo()
    {
        $sequenceThree = new Sequence(3);
        $sequenceSeven = new Sequence(7);
        $sequenceGroup = new SequenceGroup();

        $sequenceGroup->add($sequenceSeven);
        $sequenceGroup->add($sequenceThree);

        $this->assertEquals($sequenceThree->get(), $sequenceGroup->get());
    }

    public function testShouldReportSizeOfGroup()
    {
        $sequenceGroup = new SequenceGroup();
        $sequence1 = new Sequence();
        $sequence2 = new Sequence();
        $sequence3 = new Sequence();
        $sequenceGroup->add($sequence1);
        $sequenceGroup->add($sequence2);
        $sequenceGroup->add($sequence3);
        $this->assertEquals(3, $sequenceGroup->count());
    }

    public function testShouldRemoveSequenceFromGroup()
    {
        $sequenceThree = new Sequence(3);
        $sequenceSeven = new Sequence(7);
        $sequenceGroup = new SequenceGroup();

        $sequenceGroup->add($sequenceThree);
        $sequenceGroup->add($sequenceSeven);
        $this->assertEquals($sequenceThree->get(), $sequenceGroup->get());
        $this->assertTrue($sequenceGroup->remove($sequenceThree));
        $this->assertEquals($sequenceSeven->get(), $sequenceGroup->get());
        $this->assertEquals(1, $sequenceGroup->count());
    }

    public function testShouldRemoveSequenceFromGroupWhereItBeenAddedMultipleTimes()
    {
        $sequenceThree = new Sequence(3);
        $sequenceSeven = new Sequence(7);
        $sequenceGroup = new SequenceGroup();

        $sequenceGroup->add($sequenceThree);
        $sequenceGroup->add($sequenceSeven);
        $sequenceGroup->add($sequenceThree);

        $this->assertEquals($sequenceThree->get(), $sequenceGroup->get());

        $this->assertTrue($sequenceGroup->remove($sequenceThree));
        $this->assertEquals($sequenceSeven->get(), $sequenceGroup->get());
        $this->assertEquals(1, $sequenceGroup->count());
    }

    public function testShouldSetGroupSequenceToSameValue()
    {
        $sequenceThree = new Sequence(3);
        $sequenceSeven = new Sequence(7);
        $sequenceGroup = new SequenceGroup();

        $sequenceGroup->add($sequenceSeven);
        $sequenceGroup->add($sequenceThree);

        $expectedSequence = 11;
        $sequenceGroup->set($expectedSequence);

        $this->assertEquals($expectedSequence, $sequenceThree->get());
        $this->assertEquals($expectedSequence, $sequenceSeven->get());
    }

    public function testObjectEqualityThroughThreads()
    {
        $sequence = new Sequence();
        $sequenceGroup = new SequenceGroup();
        $sequenceGroup->add($sequence);
        $sequences = $sequenceGroup->getSequences();
        $groupSequence = $sequences[0];

        $this->assertNotEquals(spl_object_hash($sequence), spl_object_hash($groupSequence));
        $this->assertNotSame($sequence, $groupSequence);
        $this->assertFalse($sequence->equals($sequenceGroup));
        $this->assertTrue($sequence->equals($groupSequence));
        $this->assertTrue($groupSequence->equals($sequence));
    }


    public function testShouldAddWhileRunning()
    {
        $eventFactory = new TestEventFactory();
        $ringBuffer = RingBuffer::createSingleProducer($eventFactory, 32);
        $sequenceThree = new Sequence(3);
        $sequenceSeven = new Sequence(7);
        $sequenceGroup = new SequenceGroup();
        $sequenceGroup->add($sequenceSeven);

        for ($i = 0; $i < 11; $i++)
        {
            $ringBuffer->publish($ringBuffer->next());
        }

        $sequenceGroup->addWhileRunning($ringBuffer, $sequenceThree);
        $this->assertEquals(10, $sequenceThree->get());
    }
}
