<?php

namespace PhpDisruptor;

interface LifecycleAwareInterface
{
    /**
     * Called once on thread start before first event is available.
     *
     * @return void
     */
    public function onStart();

    /**
     * <p>Called once just before the thread is shutdown.</p>
     *
     * Sequence event processing will already have stopped before this method is called. No events will
     * be processed after this message.
     *
     * @return void
     */
    public function onShutdown();
}
