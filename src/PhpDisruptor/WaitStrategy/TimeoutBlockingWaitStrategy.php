<?php

namespace PhpDisruptor\WaitStrategy;

use Cond;
use Mutex;
use PhpDisruptor\Exception;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Pthreads\TimeUnit;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

final class TimeoutBlockingWaitStrategy extends StackableArray implements WaitStrategyInterface
{
    /**
     * @var int
     */
    public $timeoutMicros;

    /**
     * @var int
     */
    public $mutex;

    /**
     * @var int
     */
    public $cond;

    /**
     * Constructor
     *
     * @param int $timeout
     * @param TimeUnit $timeUnit
     */
    public function __construct($timeout, TimeUnit $timeUnit)
    {
        $this->timeoutMicros = $timeUnit->toMicros($timeout);
        $this->mutex = Mutex::create(false);
        $this->cond = Cond::create();
    }

    /**
     * Wait for the given sequence to be available.  It is possible for this method to return a value
     * less than the sequence number supplied depending on the implementation of the WaitStrategy.  A common
     * use for this is to signal a timeout.  Any EventProcessor that is using a WaitStragegy to get notifications
     * about message becoming available should remember to handle this case.  The BatchEventProcessor explicitly
     * handles this case and will signal a timeout if required.
     *
     * @param int $sequence to be waited on.
     * @param Sequence $cursor the main sequence from ringbuffer. Wait/notify strategies will
     *    need this as it's the only sequence that is also notified upon update.
     * @param Sequence $dependentSequence on which to wait.
     * @param SequenceBarrierInterface $barrier the processor is waiting on.
     * @return int the sequence that is available which may be greater than the requested sequence.
     * @throws Exception\AlertException if the status of the Disruptor has changed.
     * @throws Exception\InterruptedException if the thread is interrupted.
     * @throws Exception\TimeoutException
     */
    public function waitFor(
        $sequence,
        Sequence $cursor,
        Sequence $dependentSequence,
        SequenceBarrierInterface $barrier
    )
    {
        $micros = $this->timeoutMicros;

        if (($availableSequence = $cursor->get()) < $sequence) {
            Mutex::lock($this->mutex);
            while (($availableSequence = $cursor->get()) < $sequence) {
                $barrier->checkAlert();
                $s = microtime();
                Cond::wait($this->cond, $this->mutex, $micros);
                $micros = (microtime() - $s) * 1000000 - $micros;
                if ($micros <= 0) {
                    Mutex::unlock($this->mutex);
                    throw new Exception\TimeoutException();
                }
            }
            Mutex::unlock($this->mutex);
        }

        while (($availableSequence = $dependentSequence->get()) < $sequence) {
            $barrier->checkAlert();
        }

        return $availableSequence;
    }

    /**
     * Implementations should signal the waiting EventProcessors that the cursor has advanced.
     *
     * @return void
     */
    public function signalAllWhenBlocking()
    {
        Mutex::lock($this->mutex);
        Cond::broadcoast($this->cond);
        Mutex::unlock($this->mutex);
    }

    public function __destruct()
    {
        Cond::destroy($this->cond);
        Mutex::destroy($this->mutex);
    }
}
