<?php

namespace PhpDisruptor\Pthreads;

use PhpDisruptor\Pthreads\UuidStackable;
use Stackable;

class ObjectStorage extends Stackable
{
    /**
     * @var StackableArray
     */
    public $data;

    /**
     * @var StackableArray
     */
    public $info;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->data = new StackableArray();
        $this->info = new StackableArray();
    }

    public function run()
    {
    }

    /**
     * Adds an object in the storage
     *
     * @param UuidStackable $object
     * @param mixed $data [optional]
     * @return void
     */
    public function attach(UuidStackable $object, $data = null)
    {
        $this->data[] = $object;
        $this->info[] = $data;
    }

    /**
     * Removes an object from the storage
     *
     * @param UuidStackable $object
     * @return void
     */
    public function detach(UuidStackable $object)
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
     * @param UuidStackable $object
     * @return bool true if the object is in the storage, false otherwise.
     */
    public function contains(UuidStackable $object)
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
    public function removeAllExcept(self $storage)
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
     * @param UuidStackable $object
     * @return mixed The data associated with the current iterator position.
     */
    public function getInfo(UuidStackable $object)
    {
        foreach ($this->data as $key => $value) {
            if ($object->equals($value)) {
                return $this->info[$key];
            }
        }
    }

    /**
     * Sets the data associated with the current iterator entry
     *
     * @param UuidStackable $object
     * @param mixed $data
     * @return void
     */
    public function setInfo(UuidStackable $object, $data)
    {
        foreach ($this->data as $key => $value) {
            if ($object->equals($value)) {
                $this->info[$key] = $data;
                break;
            }
        }
    }
}
