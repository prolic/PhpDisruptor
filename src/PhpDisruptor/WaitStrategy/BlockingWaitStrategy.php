<?php

namespace PhpDisruptor\WaitStrategy;

use Cond;
use Mutex;
use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;

final class BlockingWaitStrategy implements WaitStrategyInterface
{
    /**
     * @var int;
     */
    private $mutex;

    /**
     * @var int
     */
    private $cond;

    /**
     * Constructor
     *
     * Creates the mutex and condition
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
    )
    {
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
            Cond::singal($this->cond);
        } catch (\Exception $e) {
            Mutex::unlock($this->mutex);
        }
        Mutex::unlock($this->mutex);
    }

    public function __destruct()
    {
        Mutex::destroy($this->mutex);
    }
}
