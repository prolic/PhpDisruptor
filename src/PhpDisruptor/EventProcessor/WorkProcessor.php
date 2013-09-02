<?php

namespace PhpDisruptor\EventProcessor;

use HumusVolatile\ZendCacheVolatile;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WorkHandlerInterface;


final class WorkProcessor implements EventProcessorInterface
{
    /**
     * @var ZendCacheVolatile
     */
    private $running;

    /**
     * @var Sequence
     */
    private $sequence;

    /**
     * @var RingBuffer
     */
    private $ringBuffer;

    /**
     * @var SequenceBarrierInterface
     */
    private $sequenceBarrier;

    /**
     * @var WorkHandlerInterface
     */
    private $workHandler;

    /**
     * @var ExceptionHandlerInterface
     */
    private $exceptionHandler;

    /**
     * @var Sequence
     */
    private $workSequence;

    

    /**
     * Get a reference to the Sequence being used by this EventProcessor.
     *
     * @return Sequence reference to the Sequence for this EventProcessor
     */
    public function getSequence()
    {
        // TODO: Implement getSequence() method.
    }

    /**
     * Signal that this EventProcessor should stop when it has finished consuming at the next clean break.
     * It will call {@link SequenceBarrierInterface#alert()} to notify the thread to check status.
     *
     * @return void
     */
    public function halt()
    {
        // TODO: Implement halt() method.
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        // TODO: Implement isRunning() method.
    }

    /**
     * @return void
     */
    public function run()
    {
        // TODO: Implement run() method.
    }

    /**
     * @return void
     */
    private function notifyStart()
    {
        // TODO: Implement notifyStart() method.
    }

    /**
     * @return void
     */
    private function notifyShutdown()
    {
        // TODO: Implement notifyShutdown() mehtod.
    }
}
