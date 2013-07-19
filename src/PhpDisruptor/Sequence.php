<?php

namespace PhpDisruptor;

use Zend\Cache\Exception\ExceptionInterface as StorageException;;
use Zend\Cache\Storage\StorageInterface;

class Sequence
{
    const INITIAL_VALUE = -1;

    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $storage;

    /**
     * Constructor
     *
     * @param int|null $initialValue
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __construct(StorageInterface $storage, $initialValue = null)
    {
        if (null === $initialValue) {
            $initialValue = self::INITIAL_VALUE;
        } else if (!is_numeric($initialValue)) {
            throw new Exception\InvalidArgumentException('initial value must be an integer or null');
        }

        $this->key = spl_object_hash($this);
        $this->storage = $storage;
        $this->set($initialValue);
    }

    /**
     * Perform a volatile read of this sequence's value.
     *
     * @return int The current value of the sequence.
     */
    public function get()
    {
        try {
            return $this->storage->getItem($this->key);
        } catch (StorageException $e) {
            throw new Exception\RuntimeException('Storage error');
        }
    }

    /**
     * Perform an ordered write of this sequence.  The intent is
     * a Store/Store barrier between this write and any previous
     * store.
     *
     * @param int $value The new value for the sequence.
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function set($value)
    {
        if (!is_numeric($value)) {
            throw new Exception\InvalidArgumentException('value must be an integer');
        }
        try {
            return $this->storage->setItem($this->key, $value);
        } catch (StorageException $e) {
            throw new Exception\RuntimeException('Storage error');
        }
    }

    /**
     * Perform a compare and set operation on the sequence.
     *
     * @param int $expectedValue The expected current value.
     * @param int $newValue The value to update to.
     * @return bool true if the operation succeeds, false otherwise.
     */
    public function compareAndSet($expectedValue, $newValue)
    {
        try {
            return $this->storage->checkAndSetItem($expectedValue, $this->key, $newValue);
        } catch (StorageException $e) {
            throw new Exception\RuntimeException('Storage error');
        }
    }

    /**
     * Atomically increment the sequence by one.
     *
     * @return int The value after the increment
     */
    public function incrementAndGet()
    {
        return $this->addAndGet(1);
    }

    /**
     * Atomically add the supplied value.
     *
     * @param int $increment The value to add to the sequence.
     * @return int The value after the increment.
     * @throws Exception\InvalidArgumentException
     */
    public function addAndGet($increment)
    {
        if (!is_numeric($increment)) {
            throw new Exception\InvalidArgumentException('increment must be an integer');
        }

        do
        {
            $currentValue = $this->get();
            $newValue = $currentValue + $increment;
        }
        while (!$this->compareAndSet($currentValue, $newValue));

        return $newValue;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}
