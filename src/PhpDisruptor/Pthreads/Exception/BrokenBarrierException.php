<?php

namespace PhpDisruptor\Pthreads\Exception;

/**
 * Exception thrown when a thread tries to wait upon a barrier that is in a broken
 * state, or which enters the broken state while the thread is waiting.
 */
class BrokenBarrierException extends \Exception implements ExceptionInterface
{
}
