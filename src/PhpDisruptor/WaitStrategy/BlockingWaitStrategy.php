<?php

namespace PhpDisruptor\WaitStrategy;

use Cond;
use Mutex;
use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use Stackable;

final class BlockingWaitStrategy extends Stackable implements WaitStrategyInterface
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
    public function run()
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
                    Cond::wait($this->mutex, $this->cond);
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
        try {
            Cond::signal($this->cond);
        } catch (\Exception $e) {
            Mutex::unlock($this->mutex);
        }
        Mutex::unlock($this->mutex);
    }

    /**
     * Destroy the mutex
     */
    public function __destruct()
    {
        Mutex::destroy($this->mutex);
    }
}
