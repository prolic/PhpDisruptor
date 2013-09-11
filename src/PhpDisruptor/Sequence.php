<?php

namespace PhpDisruptor;

use Stackable;

class Sequence extends Stackable
{
    const INITIAL_VALUE = -1;

    /**
     * @var int
     */
    protected $value;

    /**
     * Constructor
     *
     * @param int $initialValue
     */
    public function __construct($initialValue = self::INITIAL_VALUE)
    {
        $this->set($initialValue);
    }

    /**
     * Perform a volatile read of this sequence's value.
     *
     * @return int The current value of the sequence.
     */
    protected function get()
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
    protected function set($value)
    {
        $this->value = $value;
    }

    /**
     * Perform a compare and set operation on the sequence.
     *
     * @param int $expectedValue The expected current value.
     * @param int $newValue The value to update to.
     * @return bool true if the operation succeeds, false otherwise.
     */
    protected function compareAndSet($expectedValue, $newValue)
    {
        if ($this->value == $expectedValue) {
            $this->value = $newValue;
            return true;
        }
        return false;
    }

    /**
     * Atomically increment the sequence by one.
     *
     * @return int The value after the increment
     */
    protected function incrementAndGet()
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
    protected function addAndGet($increment)
    {
        do {
            $currentValue = $this->get();
            $newValue = $currentValue + $increment;
        } while (!$this->compareAndSet($currentValue, $newValue));

        return $newValue;
    }

    /**
     * @return string
     */
    protected function __toString()
    {
        return $this->get();
    }

    public function run()
    {
    }
}
