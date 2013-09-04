<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\Exception;
use PhpDisruptor\RingBuffer;
use HumusVolatile\ZendCacheVolatile;
use PhpDisruptor\SequencerFollowingSequence;
use Thread;

/**
 * No operation version of a EventProcessor that simply tracks a Sequence.
 *
 * This is useful in tests or for pre-filling a RingBuffer from a publisher.
 */
final class NoOpEventProcessor extends Thread implements AbstractEventProcessor
{
    /**
     * @var SequencerFollowingSequence
     */
    private $sequence;

    /**
     * @var ZendCacheVolatile
     */
    private $running;

    /**
     * Constructor
     *
     * @param RingBuffer $sequencer
     */
    public function __construct(RingBuffer $sequencer)
    {
        $this->running = new ZendCacheVolatile($sequencer->getStorage(), get_class($this) . '::running', false);
        $this->sequence = new SequencerFollowingSequence($sequencer);
    }

    /**
     * @return SequencerFollowingSequence
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @return void
     */
    public function halt()
    {
        do {
            $oldValue = $this->running->get();
            $return = $this->running->compareAndSwap($oldValue, false);
        } while (false == $return);
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->running->get();
    }

    /**
     * @return void
     * @throws \PhpDisruptor\Exception\RuntimeException
     */
    public function run()
    {
        if (!$this->running->compareAndSwap(false, true)) {
            throw new Exception\RuntimeException(
                'Thread is already running'
            );
        }
    }
}
