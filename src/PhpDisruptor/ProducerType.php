<?php

namespace PhpDisruptor;

/**
 * Defines producer types to support creation of RingBuffer with correct sequencer and publisher.
 */
class ProducerType extends AbstractEnum
{
    const SINGLE = "single";
    const MULTI = "multi";
}
