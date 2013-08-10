<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\Exception;
use PhpDisruptor\RingBuffer;
use HumusVolatile\ZendCacheVolatile;
use PhpDisruptor\SequencerFollowingSequence;

/**
 * No operation version of a {@link EventProcessor} that simply tracks a {@link Sequence}.
 *
 * This is useful in tests or for pre-filling a {@link RingBuffer} from a publisher.
 */
class NoOpEventProcessor implements EventProcessorInterface
{
    /**
     * @var SequencerFollowingSequence
     */
    protected $sequence;

    /**
     * @var ZendCacheVolatile
     */
    protected $running;

    /**
     * Constructor
     *
     * @param RingBuffer $sequencer
     */
    public function __construct(RingBuffer $sequencer)
    {
        $this->running = new ZendCacheVolatile($sequencer->getStorage(), $this);
        $this->running->add(false);
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
