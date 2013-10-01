<?php

namespace PhpDisruptor\Pthreads;

use Iterator;
use Stackable;

class ObjectStorage extends Stackable
{
    public $data;

    public $info;

    public $position;

    public function __construct()
    {
        $this->data = new StackableArray();
        $this->info = new StackableArray();
        $this->position = 0;
    }

    public function run()
    {
    }

    /**
     * Adds an object in the storage
     *
     * @param object $object
     * @param mixed $data [optional]
     * @return void
     */
    public function attach($object, $data = null)
    {
        $this->data[] = $object;
        $this->info[] = $data;
    }

    /**
     * Removes an object from the storage
     *
     * @param object $object
     * @return void
     */
    public function detach($object)
    {
        foreach ($this->data as $key => $value) {
            if ($value === $object) {
                unset($this->data[$key]);
                unset($this->info[$key]);
            }
        }
    }

    /**
     * Checks if the storage contains a specific object
     *
     * @param object $object
     * @return bool true if the object is in the storage, false otherwise.
     */
    public function contains($object)
    {
        foreach ($this->data as $value) {
            if ($value === $object) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adds all objects from another storage
     *
     * @param ObjectStorage $storage
     * @return void
     */
    public function addAll(self $storage)
    {
        foreach ($storage->data as $object) {
            $this->attach($object);
        }
    }

    /**
     * Removes objects contained in another storage from the current storage
     *
     * @param ObjectStorage $storage
     * @return void
     */
    public function removeAll(self $storage)
    {
        foreach ($this->data as $key => $value) {
            foreach ($storage->data as $object) {
                if ($object === $value) {
                    unset($this->data[$key]);
                    unset($this->info[$key]);
                }
            }
        }
    }

    /**
     * Removes all objects except for those contained in another storage from the current storage
     *
     * @param ObjectStorage $storage
     * @return void
     */
    public function removeAllExcept(ObjectStorage $storage)
    {
        foreach ($this->data as $key => $value) {
            foreach ($storage->data as $object) {
                if ($object !== $value) {
                    unset($this->data[$key]);
                    unset($this->info[$key]);
                }
            }
        }
    }

    /**
     * Returns the data associated with the current iterator entry
     *
     * @return mixed The data associated with the current iterator position.
     */
    public function getInfo()
    {
        $current = $this->current();
        return $this->info[$current];
    }

    /**
     * Sets the data associated with the current iterator entry
     *
     * @param mixed $data
     * @return void
     */
    public function setInfo ($data)
    {
        $current = $this->current();
        $this->info[$current] = $data;
    }
}
