<?php

namespace PhpDisruptor;

class SequencerFollowingSequence extends Sequence
{
    /**
     * @var RingBuffer
     */
    protected $sequencer;

    /**
     * Constructor
     *
     * @param RingBuffer $sequencer
     */
    public function __construct(RingBuffer $sequencer)
    {
        parent::__construct($sequencer->getStorage(), SequencerInterface::INITIAL_CURSOR_VALUE);
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
