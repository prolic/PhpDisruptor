<?php

namespace PhpDisruptor\WaitStrategy;

use HumusLock\ReentrantSleepLock;
use HumusVolatile\VolatileInterface;
use HumusVolatile\ZendCacheVolatile;
use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceBarrierInterface;
use Zend\Cache\Storage\StorageInterface;

/**
* Blocking strategy that uses a lock without a condition variable for {@link EventProcessor}s waiting on a barrier.
*
* This strategy can be used when throughput and low-latency are not as important as CPU resource.
*/
class BlockingWaitStrategy implements WaitStrategyInterface
{
    /**
     * @var ReentrantSleepLock
     */
    protected $lock;

    /**
     * Constructor
     *
     * @param StorageInterface|VolatileInterface $storageOrVolatile
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($storageOrVolatile)
    {
        if ($storageOrVolatile instanceof StorageInterface) {
            $storageOrVolatile = new ZendCacheVolatile($storageOrVolatile, $this);
        }
        if (!$storageOrVolatile instanceof VolatileInterface) {
            throw new Exception\InvalidArgumentException(
                '$storageOrVolatile must be an instance of '
                . 'Zend\Cache\Storage\StorageInterface or '
                . 'HumusVolatile\VolatileInterface'
            );
        }
        $this->lock = new ReentrantSleepLock($storageOrVolatile);
    }

    /**
     * @inheritdoc
     */
    public function waitFor(
        $sequence,
        Sequence $cursorSequence,
        Sequence $dependentSequence,
        SequenceBarrierInterface $barrier
    ) {
        if (!is_numeric($sequence)) {
            throw new Exception\InvalidArgumentException(
                '$sequence must be a string'
            );
        }
        if (($availableSequence = $cursorSequence->get()) < $sequence) {
            $this->lock->lock();
            try {
                while (($availableSequence = $cursorSequence->get()) < $sequence) {
                    $barrier->checkAlert();
                    // $this->condition->await(); disabled for non condition implementation
                }
            } catch (\Exception $e) {
                $this->lock->unlock();
                throw $e;
            }
            $this->lock->unlock();
        }

        while (($availableSequence = $dependentSequence->get()) < $sequence) {
            $barrier->checkAlert();
        }
        return $availableSequence;
    }

    public function signalAllWhenBlocking()
    {
        // disabled for non condition implementation

        //$this->lock->lock();
        //try {
        //    $this->condition->signalAll();
        //} catch (\Exception $e) {
        //    $this->lock->unlock();
        //    throw $e;
        //}
        //$this->lock->unlock();
    }
}
