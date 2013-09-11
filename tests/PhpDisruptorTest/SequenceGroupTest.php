<?php

namespace PhpDisruptorTest;

use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceGroup;
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

//    public function testShouldNotFailIfTryingToRemoveNotExistingSequence()
//    {
//        $sequenceGroup = new SequenceGroup();
//        $sequenceGroup->add(new Sequence());
//        $sequenceGroup->add(new Sequence());
//        $this->assertFalse($sequenceGroup->remove(new Sequence()));
//    }
//
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
        $sequenceGroup->add(new Sequence());
        $sequenceGroup->add(new Sequence());
        $sequenceGroup->add(new Sequence());

        $this->assertEquals(3, $sequenceGroup->size());
    }
//
//    public function testShouldRemoveSequenceFromGroup()
//    {
//        $sequenceThree = new Sequence(3);
//        $sequenceSeven = new Sequence(7);
//        $sequenceGroup = new SequenceGroup();
//
//        $sequenceGroup->add($sequenceSeven);
//        $sequenceGroup->add($sequenceThree);
//
//        $this->assertEquals($sequenceThree->get(), $sequenceGroup->get());
//
//        $this->assertTrue($sequenceGroup->remove($sequenceThree));
//        $this->assertEquals($sequenceSeven->get(), $sequenceGroup->get());
//        $this->assertEquals(1, $sequenceGroup->size());
//    }
//
//    public function testShouldRemoveSequenceFromGroupWhereItBeenAddedMultipleTimes()
//    {
//        $sequenceThree = new Sequence(3);
//        $sequenceSeven = new Sequence(7);
//        $sequenceGroup = new SequenceGroup();
//
//        $sequenceGroup->add($sequenceThree);
//        $sequenceGroup->add($sequenceSeven);
//        $sequenceGroup->add($sequenceThree);
//
//        $this->assertEquals($sequenceThree->get(), $sequenceGroup->get());
//
//        $this->assertTrue($sequenceGroup->remove($sequenceThree));
//        $this->assertEquals($sequenceSeven->get(), $sequenceGroup->get());
//        $this->assertEquals(1, $sequenceGroup->size());
//    }
//
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
//
//    /*
//    public function testShouldAddWhileRunning()
//    {
//        RingBuffer<TestEvent> ringBuffer = RingBuffer.createSingleProducer(TestEvent.EVENT_FACTORY, 32);
//        final Sequence sequenceThree = new Sequence(3L);
//        final Sequence sequenceSeven = new Sequence(7L);
//        final SequenceGroup sequenceGroup = new SequenceGroup();
//        sequenceGroup.add(sequenceSeven);
//
//        for (int i = 0; i < 11; i++)
//        {
//            ringBuffer.publish(ringBuffer.next());
//        }
//
//        sequenceGroup.addWhileRunning(ringBuffer, sequenceThree);
//        assertThat(sequenceThree.get(), is(10L));
//    }
//    */
}
