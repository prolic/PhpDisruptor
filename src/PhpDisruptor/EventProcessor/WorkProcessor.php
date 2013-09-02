<?php

namespace PhpDisruptor\EventProcessor;

use HumusVolatile\ZendCacheVolatile;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use PhpDisruptor\RingBuffer;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use PhpDisruptor\WorkHandlerInterface;
use Zend\Cache\Storage\StorageInterface;


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
     * Constructor
     *
     * @param RingBuffer $ringBuffer
     * @param SequenceBarrierInterface $sequenceBarrier
     * @param WorkHandlerInterface $workHandler
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param Sequence $workSequence
     */
    public function __construct(
        RingBuffer $ringBuffer,
        SequenceBarrierInterface $sequenceBarrier,
        WorkHandlerInterface $workHandler,
        ExceptionHandlerInterface $exceptionHandler,
        Sequence $workSequence
    ) {
        $storage = $ringBuffer->getStorage();
        $this->sequence = new Sequence($storage);
        $this->running = new ZendCacheVolatile($storage, get_class($this) . '::running', false);
        $this->ringBuffer = $ringBuffer;
        $this->sequenceBarrier = $sequenceBarrier;
        $this->workHandler = $workHandler;
        $this->exceptionHandler = $exceptionHandler;
        $this->workSequence = $workSequence;
    }

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
