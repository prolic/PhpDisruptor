<?php

namespace PhpDisruptor;

use Zend\Cache\Storage\StorageInterface;

class BlockingWaitStrategy implements WaitStrategyInterface
{
    /*
    private final Lock lock = new ReentrantLock();
    private final Condition processorNotifyCondition = lock.newCondition();
    */

    /**
     * @var LockInterface
     */
    protected $lock;

    public function __construct(ReentrantLock $lock)
    {
        $this->lock = $lock;
    }

    public function waitFor(
        $sequence,
        Sequence $cursorSequence,
        Sequence $dependentSequence,
        SequenceBarrierInterface $barrier
    ) {
        if (($availableSequence = $cursorSequence->get()) < $sequence) {

            $this->lock->lock();
            try {
                while (($availableSequence = $cursorSequence->get()) < $sequence) {
                    $barrier->checkAlert();
                }
                $this->lock->unLock();
            } catch (\Exception $e) {
                $this->lock->unLock();
            }
        }
        while (($availableSequence = $dependentSequence->get()) < $sequence) {
            $barrier->checkAlert();
        }
        return $availableSequence;
    }
}

