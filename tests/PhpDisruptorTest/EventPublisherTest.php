<?php

namespace PhpDisruptorTest;

use PhpDisruptor\EventProcessor\NoOpEventProcessor;
use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptorTest\TestAsset\LongEventFactory;

class EventPublisherTest extends \PHPUnit_Framework_TestCase implements EventTranslatorInterface
{
    const RINGBUFFER_SIZE = 32;

    /**
     * @var RingBuffer
     */
    protected $ringBuffer;

    protected function setUp()
    {
        $this->ringBuffer = RingBuffer::createMultiProducer(new LongEventFactory(), self::RINGBUFFER_SIZE);
    }

    public function testShouldPublishEvent()
    {
        $eventProcessor = new NoOpEventProcessor($this->ringBuffer);
        $sequences = new StackableArray();
        $sequences[] = $eventProcessor->getSequence();

        $this->ringBuffer->addGatingSequences($sequences);

        $this->ringBuffer->publishEvent($this);
        $this->ringBuffer->publishEvent($this);

        $this->assertEquals($this->ringBuffer->get(0)->get(), 29);
        $this->assertEquals($this->ringBuffer->get(1)->get(), 30);
    }

    public function testShouldTryPublishEvent()
    {
        $sequences = new StackableArray();
        $sequences[] = new Sequence();
        $this->ringBuffer->addGatingSequences($sequences);

        for ($i = 0; $i < self::RINGBUFFER_SIZE; $i++) {
            $this->assertTrue($this->ringBuffer->tryPublishEvent($this));
        }

        for ($i = 0; $i < self::RINGBUFFER_SIZE; $i++) {
            $this->assertEquals($this->ringBuffer->get($i)->get(), $i + 29);
        }

        $this->assertFalse($this->ringBuffer->tryPublishEvent($this));
    }

    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return __NAMESPACE__ . '\TestAsset\LongEvent';
    }

    /**
     * Translate a data representation into fields set in given event
     *
     * @param object $event into which the data should be translated.
     * @param int $sequence that is assigned to event.
     * @param array $args
     * @return void
     */
    public function translateTo($event, $sequence, array $args = array())
    {
        $event->set($sequence + 29);
    }

}
