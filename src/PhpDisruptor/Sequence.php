<?php

namespace PhpDisruptor;

use Zend\Cache\Exception\ExceptionInterface as StorageException;
use Zend\Cache\Storage\StorageInterface;

class Sequence
{
    const INITIAL_VALUE = -1;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Constructor
     *
     * @param StorageInterface $storage
     * @param int|null $initialValue
     * @param string|null $key (only for internal use, please don't set the key yourself)
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __construct(StorageInterface $storage, $initialValue = null, $key = null)
    {
        $initialValue = $this->init($storage, $initialValue, $key);
        $this->set($initialValue);
    }

    /**
     * @param StorageInterface $storage
     * @param null $initialValue
     * @param null $key
     * @return int|null $initialValue
     */
    protected function init(StorageInterface $storage, $initialValue, $key)
    {
        if (null === $initialValue) {
            $initialValue = self::INITIAL_VALUE;
        } elseif (!is_numeric($initialValue)) {
            throw new Exception\InvalidArgumentException('initial value must be an integer or null');
        }

        if (null === $key) {
            $key = 'sequence_' . sha1(gethostname() . getmypid() . microtime(true) . spl_object_hash($this));
        }

        $this->key = $key;
        $this->storage = $storage;
        return $initialValue;
    }

    /**
     * Instantiate sequence from sequence key
     *
     * @param StorageInterface $storage
     * @param string $key
     * @return Sequence
     */
    public static function fromKey(StorageInterface $storage, $key)
    {
        $initialValue = $storage->getItem($key);
        $sequence = new static($storage, $initialValue, $key);
        return $sequence;
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

        do {
            $currentValue = $this->get();
            $newValue = $currentValue + $increment;
        } while (!$this->compareAndSet($currentValue, $newValue));

        return $newValue;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }

    /**
     * Get the sequence key
     * (only useful for internal use)
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param Sequence $sequence
     * @return bool
     */
    public function equals(Sequence $sequence)
    {
        return ($this->getKey() == $sequence->getKey());
    }
}
