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
     * Constructor
     *
     * @param RingBuffer $sequencer
     */
    public function __construct(RingBuffer $sequencer)
    {
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
    }
}
