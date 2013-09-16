<?php

namespace PhpDisruptor;

final class SequencerFollowingSequence extends Sequence
{
    /**
     * @var RingBuffer
     */
    public $sequencer;

    /**
     * Constructor
     *
     * @param RingBuffer $sequencer
     */
    public function __construct(RingBuffer $sequencer)
    {
        parent::__construct(SequencerInterface::INITIAL_CURSOR_VALUE);
        $this->sequencer = $sequencer;
    }

    /**
     * @return int
     */
    public function get()
    {
        return $this->sequencer->getCursor();
    }
}
