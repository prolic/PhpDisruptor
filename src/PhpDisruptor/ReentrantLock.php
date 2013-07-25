<?php

namespace PhpDisruptor;

use DateInterval;
use DateTime;
use Zend\Cache\Storage\StorageInterface;

class ReentrantLock implements LockInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var string
     */
    protected $key;

    /**
     * Constructor
     *
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
        $this->key = 'lock_' . sha1(gethostname() . getmypid() . microtime(true) . spl_object_hash($this));
    }

    /**
     * Acquires the lock.
     *
     * @return void
     */
    public function lock()
    {
        do {
            $result = $this->storage->setItem($this->key, 'locked');
        } while (false === $result);
    }

    /**
     * Acquires the lock if it is free within the given waiting time and the current thread has not been interrupted.
     *
     * @param DateInterval|null $interval
     * @return bool
     */
    public function tryLock(DateInterval $interval = null)
    {
        $dateTime = new DateTime();
        $target = $dateTime->add($interval);
        do {
            $result = $this->storage->hasItem($this->key);
            if (false === $result) {
                $result = $this->storage->setItem($this->key , 'locked');
            }
            $calculatedTime = $target->diff(new DateTime);
            $timeout = false;
            if ($calculatedTime->y > 0) {
                $timeout = true;
            }
            if ($calculatedTime->m > 0) {
                $timeout = true;
            }
            if ($calculatedTime->d > 0) {
                $timeout = true;
            }
            if ($calculatedTime->h > 0) {
                $timeout = true;
            }
            if ($calculatedTime->i > 0) {
                $timeout = true;
            }
            if ($calculatedTime->s > 0) {
                $timeout = true;
            }
            if ($calculatedTime->days > 0) {
                $timeout = true;
            }
        } while (false === $result || $timeout);
        return $result;
    }

    /**
     * Releases the lock.
     *
     * @return void
     */
    public function unLock()
    {
        $this->storage->removeItem($this->key);
    }
}
