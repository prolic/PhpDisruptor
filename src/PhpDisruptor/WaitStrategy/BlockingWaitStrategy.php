<?php

namespace PhpDisruptor\WaitStrategy;

use Cond;
use Mutex;
use PhpDisruptor\Exception;
use ConcurrentPhpUtils\NoOpStackable;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

final class BlockingWaitStrategy extends NoOpStackable implements WaitStrategyInterface
{
    /**
     * @var int;
     */
    public $mutex;

    /**
     * @var int
     */
    public $cond;

    /**
     * Creates the mutex and condition
     *
     * @inheritdoc
     */
    public function __construct()
    {
        $this->mutex = Mutex::create(false);
        $this->cond = Cond::create();
    }

    /**
     * @inheritdoc
     */
    public function waitFor(
        $sequence,
        Sequence $cursor,
        Sequence $dependentSequence,
        SequenceBarrierInterface $barrier
    ) {
        if (($availableSequence = $cursor->get()) < $sequence) {
            Mutex::lock($this->mutex);
            try {
                while (($availableSequence = $cursor->get()) < $sequence) {
                    $barrier->checkAlert();
                    Cond::wait($this->cond, $this->mutex);
                }
            } catch (\Exception $e) {
                Mutex::unlock($this->mutex);
            }
            Mutex::unlock($this->mutex);
        }

        while (($availableSequence = $cursor->get()) < $sequence) {
            $barrier->checkAlert();
        }

        return $availableSequence;
    }

    /**
     * @inheritdoc
     */
    public function signalAllWhenBlocking()
    {
        Mutex::lock($this->mutex);
        Cond::broadcast($this->cond);
        Mutex::unlock($this->mutex);
    }

    /**
     * Destroy the mutex
     */
    public function __destruct()
    {
        Cond::destroy($this->cond);
        Mutex::destroy($this->mutex);
    }
}
