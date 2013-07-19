<?php

namespace PhpDisruptor;

use Exception;

interface ExceptionHandlerInterface
{
    /**
     * <p>Strategy for handling uncaught exceptions when processing an event.</p>
     *
     * <p>If the strategy wishes to terminate further processing by the {@link BatchEventProcessor}
     * then it should throw a {@link RuntimeException}.</p>
     *
     * @param Exception $ex the exception that propagated from the {@link EventHandler}.
     * @param int $sequence of the event which cause the exception.
     * @param object $event being processed when the exception occurred.  This can be null.
     * @return void
     */
    public function handleEventException(Exception $ex, $sequence, $event);

    /**
     * Callback to notify of an exception during {@link LifecycleAware#onStart()}
     *
     * @param Exception $ex throw during the starting process.
     * @return void
     */
    public function handleOnStartException(Exception $ex);

    /**
     * Callback to notify of an exception during {@link LifecycleAware#onShutdown()}
     *
     * @param Exception $ex throw during the shutdown process.
     */
    public function handleOnShutdownException(Exception $ex);
}
