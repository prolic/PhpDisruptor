<?php

namespace PhpDisruptor;

use PhpDisruptor\Pthreads\AbstractAtomicStackable;

class Sequence extends AbstractAtomicStackable
{
    const INITIAL_VALUE = -1;

    /**
     * @var int
     */
    public $value;

    /**
     * @var string
     */
    public $hash;

    /**
     * Constructor
     *
     * @param int $initialValue
     */
    public function __construct($initialValue = self::INITIAL_VALUE)
    {
        $this->set($initialValue);
        $this->hash = uuid_create();
    }

    /**
     * Perform a volatile read of this sequence's value.
     *
     * @return int The current value of the sequence.
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Perform an ordered write of this sequence.  The intent is
     * a Store/Store barrier between this write and any previous
     * store.
     *
     * @param int $value The new value for the sequence.
     * @return void
     */
    public function set($value)
    {
        $this->value = $value;
    }

    /**
     * Perform a compare and swap operation on the sequence.
     *
     * @param int $oldValue The expected current value.
     * @param int $newValue The value to update to.
     * @return bool true if the operation succeeds, false otherwise.
     */
    public function compareAndSwap($oldValue, $newValue)
    {
        return $this->casMember('value', $oldValue, $newValue);
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
        do {
            $currentValue = $this->get();
            $newValue = $currentValue + $increment;
        } while (!$this->compareAndSwap($currentValue, $newValue));

        return $newValue;
    }

    /**
     * Compares two sequences by its internal hashes
     *
     * because of php/pthreads limitations we don't have object identity between threads
     *
     * @param Sequence $other
     * @return bool
     */
    public function equals(Sequence $other)
    {
        $result = (int) uuid_compare($this->hash, $other->hash);
        return 0 == $result;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get();
    }
}
