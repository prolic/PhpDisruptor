<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\Exception;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\SequencerFollowingSequence;

/**
 * No operation version of a EventProcessor that simply tracks a Sequence.
 *
 * This is useful in tests or for pre-filling a RingBuffer from a publisher.
 */
final class NoOpEventProcessor extends AbstractEventProcessor
{
    /**
     * @var SequencerFollowingSequence
     */
    public $sequence;

    /**
     * @var bool
     */
    public $running  = false;

    /**
     * Constructor
     *
     * @param RingBuffer $sequencer
     */
    public function __construct(RingBuffer $sequencer)
    {
        $this->running = false;
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
            $oldValue = $this->running;
        } while (!$this->casMember('running', $oldValue, false));
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->running;
    }

    /**
     * @return void
     * @throws Exception\RuntimeException
     */
    public function run()
    {
        if (!$this->casMember('running', false, true)) {
            throw new Exception\RuntimeException(
                'Thread is already running'
            );
        }
    }
}
