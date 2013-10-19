<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventFactoryInterface;
use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\Exception\InsufficientCapacityException;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptorTest\TestAsset\ArrayEventTranslator;
use PhpDisruptorTest\TestAsset\ArrayFactory;
use PhpDisruptorTest\TestAsset\EventTranslator;
use PhpDisruptorTest\TestAsset\StubEvent;
use PhpDisruptorTest\TestAsset\StubEventFactory;
use PhpDisruptorTest\TestAsset\StubEventTranslator;

class RingBufferTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventFactoryInterface
     */
    protected $eventFactory;

    /**
     * @var RingBuffer
     */
    protected $ringBuffer;

    /**
     * @var SequenceBarrierInterface
     */
    protected $sequenceBarrier;

    protected function setUp()
    {
        $this->eventFactory = new StubEventFactory();
        $this->ringBuffer = RingBuffer::createMultiProducer($this->eventFactory, 32);
        $this->sequenceBarrier = $this->ringBuffer->newBarrier();

        $eventProcessor = new NoOpEventProcessor($this->ringBuffer);
        $sequences = new StackableArray();
        $sequences[] = $eventProcessor->getSequence();
        $this->ringBuffer->addGatingSequences($sequences);
    }

    public function testShouldClaimAndGet()
    {
        $this->assertEquals(Sequence::INITIAL_VALUE, $this->ringBuffer->getCursor());

        $eventTranslator = new StubEventTranslator();
        $expectedEvent = new StubEvent(2701);
        $args = new StackableArray();
        $args[] = $expectedEvent->getValue();
        $args[] = $expectedEvent->getTestString();
        $this->ringBuffer->publishEvent($eventTranslator, $args);

        $sequence = $this->sequenceBarrier->waitFor(0);
        $this->assertEquals(0, $sequence);

        $event = $this->ringBuffer->get($sequence);
        $this->assertEquals($expectedEvent, $event);

        $this->assertEquals(0, $this->ringBuffer->getCursor());
    }

    public function testShouldClaimAndGetInSeparateThread()
    {
        $this->markTestIncomplete();
    }

    public function testShouldClaimAndGetMultipleMessages()
    {
        $eventTranslator = new StubEventTranslator();
        $numMessages = $this->ringBuffer->getBufferSize();
        for ($i = 0; $i < $numMessages; $i++) {
            $args = new StackableArray();
            $args[] = $i;
            $args[] = '';
            $this->ringBuffer->publishEvent($eventTranslator, $args);
        }

        $expectedSequence = $numMessages - 1;
        $available = $this->sequenceBarrier->waitFor($expectedSequence);
        $this->assertEquals($expectedSequence, $available);

        for ($i = 0; $i < $numMessages; $i++) {
            $this->assertEquals($i, $this->ringBuffer->get($i)->getValue());
        }
    }

    public function testShouldWrap()
    {
        $eventTranslator = new StubEventTranslator();
        $numMessages = $this->ringBuffer->getBufferSize();
        $offset = 1000;

        for ($i = 0; $i < $numMessages + $offset; $i++) {
            $args = new StackableArray();
            $args[0] = $i;
            $args[1] = '';
            $this->ringBuffer->publishEvent($eventTranslator, $args);
        }

        $expectedSequence = $numMessages + $offset - 1;
        $available = $this->sequenceBarrier->waitFor($expectedSequence);
        $this->assertEquals($expectedSequence, $available);

        for ($i = $offset; $i < $numMessages + $offset; $i++) {
            $value = $this->ringBuffer->get($i)->getValue();
            $this->assertEquals($i, $value);
        }
    }

    public function testShouldPreventWrapping()
    {
        $eventFactory = new StubEventFactory();
        $eventTranslator = new StubEventTranslator();

        $sequence = new Sequence();
        $sequences = new StackableArray();
        $sequences[] = $sequence;
        $ringBuffer = RingBuffer::createMultiProducer($eventFactory, 4);
        $ringBuffer->addGatingSequences($sequences);

        $arg0 = new StackableArray();
        $arg0[] = 0;
        $arg0[] = 0;
        $ringBuffer->publishEvent($eventTranslator, $arg0);
        $arg1 = new StackableArray();
        $arg1[] = 1;
        $arg1[] = 1;
        $ringBuffer->publishEvent($eventTranslator, $arg1);
        $arg2 = new StackableArray();
        $arg2[] = 2;
        $arg2[] = 2;
        $ringBuffer->publishEvent($eventTranslator, $arg2);
        $arg3 = new StackableArray();
        $arg3[] = 3;
        $arg3[] = 3;
        $ringBuffer->publishEvent($eventTranslator, $arg3);

        $this->assertFalse($ringBuffer->tryPublishEvent($eventTranslator, $arg3));
    }

    public function testShouldThrowExceptionIfBufferIsFull()
    {
        $sequences = new StackableArray();
        $sequences[] = new Sequence($this->ringBuffer->getBufferSize());

        $this->ringBuffer->addGatingSequences($sequences);

        try {
            for ($i = 0; $i < $this->ringBuffer->getBufferSize(); $i++) {
                $this->ringBuffer->publish($this->ringBuffer->tryNext());
            }
        } catch (\Exception $e) {
            $this->fail('Should not of thrown exception');
        }

        try {
            $this->ringBuffer->tryNext();
            $this->fail('Exception should have been thrown');
        } catch (InsufficientCapacityException $e) {
        }
    }

    public function testShouldPreventPublishersOvertakingEventProcessorWrapPoint()
    {
        $this->markTestIncomplete();
    }

    public function testShouldPublishEvent()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();

        $ringBuffer->publishEvent($translator);
        $ringBuffer->tryPublishEvent($translator);

        $this->assertRingBufferWithEvents($ringBuffer, array('-0', '-1'));
    }

    public function testShouldPublishEvents()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();

        $translators = new StackableArray();
        $translators[] = $translator;
        $translators[] = $translator;

        $ringBuffer->publishEvents($translators);
        $this->assertTrue($ringBuffer->tryPublishEvents($translators));

        $this->assertRingBufferWithEvents($ringBuffer, array('-0', '-1', '-2', '-3'));
    }

    public function testShouldPublishEventWithArgs()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();

        $args = $this->prepareArgs();

        $ringBuffer->publishEvent($translator, $args);
        $ringBuffer->tryPublishEvent($translator, $args);

        $this->assertRingBufferWithEvents($ringBuffer, array('Foo0Foo1Foo2Foo3-0', 'Foo0Foo1Foo2Foo3-1'));
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsIfBatchIsLargerThanRingBuffer()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();

        $translators = new StackableArray();
        $translators[] = $translator;
        $translators[] = $translator;
        $translators[] = $translator;
        $translators[] = $translator;
        $translators[] = $translator;

        try {
            $ringBuffer->publishEvents($translators);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    public function testShouldPublishEventsWithBatchSizeOfOne()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();

        $translators = new StackableArray();
        $translators[] = $translator;
        $translators[] = $translator;
        $translators[] = $translator;

        $ringBuffer->publishEvents($translators, 0, 1);
        $this->assertTrue($ringBuffer->tryPublishEvents($translators, 0, 1));

        $this->assertRingBufferWithEvents($ringBuffer, array('-0', '-1', null, null));
    }

    public function testShouldPublishEventsWithinBatch()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();

        $translators = new StackableArray();
        $translators[] = $translator;
        $translators[] = $translator;
        $translators[] = $translator;

        $ringBuffer->publishEvents($translators, 1, 2);
        $this->assertTrue($ringBuffer->tryPublishEvents($translators, 1, 2));

        $this->assertRingBufferWithEvents($ringBuffer, array('-0', '-1', '-2', '-3'));
    }

    public function testShouldPublishEventsWithArgs()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo';
        $arg0[] = 'Bar';
        $arg0[] = 'Baz';
        $arg0[] = 'Bam';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo';
        $arg1[] = 'Bar';
        $arg1[] = 'Baz';
        $arg1[] = 'Bam';

        $args[] = $arg0;
        $args[] = $arg1;

        $ringBuffer->publishEvents($translators, 0, null, $args);
        $this->assertTrue($ringBuffer->tryPublishEvents($translators, 0, null, $args));

        $this->assertRingBufferWithEvents($ringBuffer, array(
            'FooBarBazBam-0',
            'FooBarBazBam-1',
            'FooBarBazBam-2',
            'FooBarBazBam-3'
        ));
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWithArgsIfBatchIsLargerThanRingBuffer()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo';
        $arg0[] = 'Bar';
        $arg0[] = 'Baz';
        $arg0[] = 'Bam';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo';
        $arg1[] = 'Bar';
        $arg1[] = 'Baz';
        $arg1[] = 'Bam';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo';
        $arg2[] = 'Bar';
        $arg2[] = 'Baz';
        $arg2[] = 'Bam';

        $arg3 = new StackableArray();
        $arg3[] = 'Foo';
        $arg3[] = 'Bar';
        $arg3[] = 'Baz';
        $arg3[] = 'Bam';

        $arg4 = new StackableArray();
        $arg4[] = 'Foo';
        $arg4[] = 'Bar';
        $arg4[] = 'Baz';
        $arg4[] = 'Bam';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;
        $args[] = $arg3;
        $args[] = $arg4;

        try {
            $ringBuffer->publishEvents($translators, 0, null, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    public function testShouldPublishEventsWithArgsBatchSizeOfOne()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo';
        $arg0[] = 'Bar';
        $arg0[] = 'Baz';
        $arg0[] = 'Bam';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo';
        $arg1[] = 'Bar';
        $arg1[] = 'Baz';
        $arg1[] = 'Bam';

        $args[] = $arg0;
        $args[] = $arg1;

        $ringBuffer->publishEvents($translators, 0, 1, $args);
        $this->assertTrue($ringBuffer->tryPublishEvents($translators, 0, 1, $args));

        $this->assertRingBufferWithEvents($ringBuffer, array('FooBarBazBam-0', 'FooBarBazBam-1', null, null));
    }

    public function testShouldPublishEventsWithArgsWithinBatch()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        $ringBuffer->publishEvents($translators, 1, 2, $args);

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo3';
        $arg0[] = 'Bar3';
        $arg0[] = 'Baz3';
        $arg0[] = 'Bam3';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo4';
        $arg1[] = 'Bar4';
        $arg1[] = 'Baz4';
        $arg1[] = 'Bam4';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo5';
        $arg2[] = 'Bar5';
        $arg2[] = 'Baz5';
        $arg2[] = 'Bam5';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        $this->assertTrue($ringBuffer->tryPublishEvents($translators, 1, 2, $args));
        $this->assertRingBufferWithEvents($ringBuffer, array(
            'Foo1Bar1Baz1Bam1-0',
            'Foo2Bar2Baz2Bam2-1',
            'Foo4Bar4Baz4Bam4-2',
            'Foo5Bar5Baz5Bam5-3'
        ));
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWhenBatchSizeIs0()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        try {
            $ringBuffer->publishEvents($translators, 1, 0);
            $ringBuffer->tryPublishEvents($translators, 1, 0);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWhenBatchExtendsPastEndOfArray()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;
        $translators[] = $translator;
        $translators[] = $translator;

        try {
            $ringBuffer->publishEvents($translators, 1, 3);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotTryPublishEventsWhenBatchExtendsPastEndOfArray()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new ArrayEventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;
        $translators[] = $translator;
        $translators[] = $translator;


        try {
            $ringBuffer->tryPublishEvents($translators, 1, 3);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWhenBatchSizeIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new ArrayEventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        try {
            $ringBuffer->tryPublishEvents($translators, 1, -1);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotTryPublishEventsWhenBatchSizeIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new ArrayEventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = $this->prepareArgs();

        try {
            $ringBuffer->tryPublishEvents($translators, -1, -1, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWhenBatchStartsAtIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new ArrayEventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = $this->prepareArgs();

        try {
            $ringBuffer->publishEvents($translators, -1, 2, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotTryPublishEventsWhenBatchStartsAtIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new ArrayEventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = $this->prepareArgs();

        try {
            $ringBuffer->tryPublishEvents($translators, -1, 2, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWithArgsWhenBatchExtendsPastEndOfArray()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        try {
            $ringBuffer->publishEvents($translators, 1, 3, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWithArgsWhenBatchSizeIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        try {
            $ringBuffer->publishEvents($translators, 1, -1, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWithArgsWhenBatchStartsAtIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        try {
            $ringBuffer->publishEvents($translators, -1, 2, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotPublishEventsWithArgsWhenBatchSizeIs0()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        try {
            $ringBuffer->publishEvents($translators, 1, 0, $args);
            $this->assertFalse($ringBuffer->tryPublishEvents($translators, 1, 0, $args));
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotTryPublishEventsWithArgsWhenBatchExtendsPastEndOfArray()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        try {
            $ringBuffer->publishEvents($translators, 1, 0, $args);
            $this->assertFalse($ringBuffer->tryPublishEvents($translators, 1, 0, $args));
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotTryPublishEventsWithArgsWhenBatchSizeIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        try {
            $ringBuffer->publishEvents($translators, 1, -1, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    /**
     * @expectedException PhpDisruptor\Exception\InvalidArgumentException
     */
    public function testShouldNotTryPublishEventsWithArgsWhenBatchStartsAtIsNegative()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 4);
        $translator = new EventTranslator();
        $translators = new StackableArray();
        $translators[] = $translator;

        $args = new StackableArray();

        $arg0 = new StackableArray();
        $arg0[] = 'Foo0';
        $arg0[] = 'Bar0';
        $arg0[] = 'Baz0';
        $arg0[] = 'Bam0';

        $arg1 = new StackableArray();
        $arg1[] = 'Foo1';
        $arg1[] = 'Bar1';
        $arg1[] = 'Baz1';
        $arg1[] = 'Bam1';

        $arg2 = new StackableArray();
        $arg2[] = 'Foo2';
        $arg2[] = 'Bar2';
        $arg2[] = 'Baz2';
        $arg2[] = 'Bam2';

        $args[] = $arg0;
        $args[] = $arg1;
        $args[] = $arg2;

        try {
            $ringBuffer->publishEvents($translators, -1, 2, $args);
        } catch (\Exception $e) {
            $this->assertEmptyRingBuffer($ringBuffer);
            throw $e;
        }
    }

    public function testShouldAddAndRemoveSequences()
    {
        $arrayFactory = new ArrayFactory(1);
        $ringBuffer = RingBuffer::createSingleProducer($arrayFactory, 16);

        $sequenceThree = new Sequence(-1);
        $sequenceSeven = new Sequence(-1);

        $sequences = new StackableArray();
        $sequences[] = $sequenceThree;
        $sequences[] = $sequenceSeven;

        $ringBuffer->addGatingSequences($sequences);

        for ($i = 0; $i < 10; $i++) {
            $ringBuffer->publish($ringBuffer->next());
        }

        $sequenceThree->set(3);
        $sequenceSeven->set(7);

        $this->assertEquals(3, $ringBuffer->getMinimumGatingSequence());
        $this->assertTrue($ringBuffer->removeGatingSequence($sequenceThree));
        $this->assertEquals(7, $ringBuffer->getMinimumGatingSequence());
    }

    public function testShouldHandleResetToAndNotWrapUnnecessarilySingleProducer()
    {
        $eventFactory = new StubEventFactory();
        $ringBuffer = RingBuffer::createSingleProducer($eventFactory, 4);
        $this->assertHandleResetAndNotWrap($ringBuffer);
    }

    public function testShouldHandleResetToAndNotWrapUnnecessarilyMultiProducer()
    {
        $eventFactory = new StubEventFactory();
        $ringBuffer = RingBuffer::createMultiProducer($eventFactory, 4);
        $this->assertHandleResetAndNotWrap($ringBuffer);
    }

    private function assertHandleResetAndNotWrap(RingBuffer $rb)
    {
        $sequences = new StackableArray();
        $sequence = new Sequence();
        $sequences[] = $sequence;
        $rb->addGatingSequences($sequences);

        for ($i = 0; $i < 128; $i++) {
            $rb->publish($rb->next());
            $sequence->incrementAndGet();
        }

        $this->assertEquals(127, $rb->getCursor());

        $rb->resetTo(31);
        $sequence->set(31);

        for ($i = 0; $i < 4; $i++) {
            $rb->publish($rb->next());
        }

        $this->assertFalse($rb->hasAvailableCapacity(1));
    }

    private function assertEmptyRingBuffer(RingBuffer $ringBuffer)
    {
        $event0 = $ringBuffer->get(0);
        $this->assertNull($event0[0]);

        $event1 = $ringBuffer->get(1);
        $this->assertNull($event1[0]);

        $event2 = $ringBuffer->get(0);
        $this->assertNull($event2[0]);

        $event3 = $ringBuffer->get(0);
        $this->assertNull($event3[0]);
    }

    /**
     * @return StackableArray
     */
    private function prepareArgs()
    {
        $foo0 = new StackableArray();
        $foo0[] = 'Foo0';

        $foo1 = new StackableArray();
        $foo1[] = 'Foo1';

        $foo2 = new StackableArray();
        $foo2[] = 'Foo2';

        $foo3 = new StackableArray();
        $foo3[] = 'Foo3';

        $args = new StackableArray();
        $args[] = $foo0;
        $args[] = $foo1;
        $args[] = $foo2;
        $args[] = $foo3;

        return $args;
    }

    private function assertRingBufferWithEvents(RingBuffer $ringBuffer, array $results)
    {
        $events = array();
        foreach ($results as $key => $result) {
            $events[$key] = $ringBuffer->get($key);
            $this->assertEquals($result, $events[$key][0]);
        }
    }
}
