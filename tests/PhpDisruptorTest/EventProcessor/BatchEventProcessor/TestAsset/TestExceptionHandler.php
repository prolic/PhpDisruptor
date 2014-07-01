<?php

namespace PhpDisruptorTest\EventProcessor\BatchEventProcessor\TestAsset;

use Exception;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use Threaded;

class TestExceptionHandler extends Threaded implements ExceptionHandlerInterface
{
    /**
     * @var Threaded
     */
    public $result;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->result = new Threaded();
    }

    /**
     * Strategy for handling uncaught exceptions when processing an event.
     *
     * If the strategy wishes to terminate further processing by the BatchEventProcessor
     * then it should throw a RuntimeException.
     *
     * @param Exception $ex the exception that propagated from the EventHandler.
     * @param int $sequence of the event which cause the exception.
     * @param object $event being processed when the exception occurred.  This can be null.
     * @return void
     */
    public function handleEventException(Exception $ex, $sequence, $event)
    {
        $this->result[] = __METHOD__ . get_class($ex) . '-' . $sequence . '-' . get_class($event);
    }

    /**
     * Callback to notify of an exception during LifecycleAware#onStart()
     *
     * @param Exception $ex throw during the starting process.
     * @return void
     */
    public function handleOnStartException(Exception $ex)
    {
        $this->result[] = __METHOD__ . get_class($ex);
    }

    /**
     * Callback to notify of an exception during LifecycleAware#onShutdown()
     *
     * @param Exception $ex throw during the shutdown process.
     */
    public function handleOnShutdownException(Exception $ex)
    {
        $this->result[] = __METHOD__ . get_class($ex);
    }

    /**
     * @return Threaded
     */
    public function getResult()
    {
        return $this->result;
    }
}
