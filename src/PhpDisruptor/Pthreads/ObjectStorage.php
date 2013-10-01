<?php

namespace PhpDisruptor\Pthreads;

use Countable;
use Iterator;
use Serializable;
use Traversable;

class ObjectStorage implements  Countable, Iterator, Traversable
{
    /**
     * Adds an object in the storage
     *
     * @param object $object
     * @return void
     */
    public function attach ($object) {}

    /**
     * Removes an object from the storage
     *
     * @param object $object
     * @return void
     */
    public function detach ($object) {}

    /**
     * Checks if the storage contains a specific object
     *
     * @param object $object
     * @return bool true if the object is in the storage, false otherwise.
     */
    public function contains ($object) {}

    /**
     * Adds all objects from another storage
     *
     * @param ObjectStorage $storage
     * @return void
     */
    public function addAll (self $storage) {}

    /**
     * Removes objects contained in another storage from the current storage
     *
     * @param ObjectStorage $storage
     * @return void
     */
    public function removeAll (self $storage) {}

    /**
     * Removes all objects except for those contained in another storage from the current storage
     *
     * @param ObjectStorage $storage
     * @return void
     */
    public function removeAllExcept (ObjectStorage $storage) {}

    /**
     * Returns the number of objects in the storage
     *
     * @return int The number of objects in the storage.
     */
    public function count () {}

    /**
     * Rewind the iterator to the first storage element
     *
     * @return void
     */
    public function rewind () {}

    /**
     * Returns if the current iterator entry is valid
     *
     * @return bool true if the iterator entry is valid, false otherwise.
     */
    public function valid () {}

    /**
     * Returns the index at which the iterator currently is
     *
     * @return int The index corresponding to the position of the iterator.
     */
    public function key () {}

    /**
     * Returns the current storage entry
     *
     * @return object The object at the current iterator position.
     */
    public function current () {}

    /**
     * Move to the next entry
     *
     * @return void
     */
    public function next () {}
}
