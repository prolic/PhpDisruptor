<?php

namespace PhpDisruptor;

/**
 * Callback interface to be implemented for processing events as they become available in the {@link RingBuffer}
 *
 * @see BatchEventProcessor#setExceptionHandler(ExceptionHandler)
 */
interface EventHandlerInterface extends EventClassCapableInterface
{
    /**
     * Called when a publisher has published an event to the {@link RingBuffer}
     *
     * @param object $event published to the {@link RingBuffer}
     * @param int $sequence of the event being processed
     * @param bool $endOfBatch flag to indicate if this is the last event in a batch from the {@link RingBuffer}
     * @return void
     * @throws Exception\ExceptionInterface if the EventHandler would like the exception handled further up the chain.
     */
    public function onEvent($event, $sequence, $endOfBatch);
}
