<?php

namespace PhpDisruptor;

use PhpDisruptor\Util\Util;
use Zend\Cache\Storage\StorageInterface;

class SequenceGroup extends Sequence implements SequenceHolderInterface
{
    /**
     * Constructor
     *
     * @param StorageInterface $storage
     * @param string|null $key (only for internal use, please don't set the key yourself)
     */
    public function __construct(StorageInterface $storage, $key = null)
    {
        $this->init($storage, static::INITIAL_VALUE, $key);
        $this->storage->setItem($this->key, array());
    }

    /**
     * Get the sequences
     *
     * @return Sequence[]
     */
    public function getSequences()
    {
        $sequences = array();
        $content = $this->storage->getItem($this->key);
        foreach ($content as $sequence) {
            $sequences[] = Sequence::fromKey($this->storage, $sequence);
        }
        return $sequences;
    }

    /**
     * Cas the sequences
     *
     * @param array $sequences
     * @return bool, true on success, false on failure, (will always use cas)
     * @throws Exception\InvalidArgumentException
     */
    public function casSequences(array $sequences)
    {
        $oldSequences = $this->getSequences();
        foreach ($sequences as $sequence) {
            if (!$sequence instanceof Sequence) {
                throw new Exception\InvalidArgumentException(
                    '$sequences must be an array of PhpDisruptor\Sequence'
                );
            }
        }
        return $this->storage->checkAndSetItem($oldSequences, $this->key, $sequences);
    }

    /**
     * Get the minimum sequence value for the group.
     *
     * @return int the minimum sequence value for the group.
     */
    public function get()
    {
        return Util::getMinimumSequence($this->getSequences());
    }

    /**
     * Set all {@link Sequence}s in the group to a given value.
     *
     * @param int $value to set the group of sequences to.
     * @return void
     * @throws Exception\ExceptionInterface
     */
    public function set($value)
    {
        if (!is_numeric($value)) {
            throw new Exception\InvalidArgumentException('value must be an integer');
        }
        $sequences = $this->getSequences();
        foreach ($sequences as $sequence) {
            $sequence->set($value);
        }
    }

    /**
     * Add a {@link Sequence} into this aggregate.  This should only be used during
     * initialisation.  Use {@link SequenceGroup#addWhileRunning(Cursored, Sequence)}
     *
     * @param Sequence $sequence to be added to the aggregate.
     * @return void
     */
    public function add(Sequence $sequence)
    {
        do {
            $oldSequences = $this->getSequences();

            $oldContent = array();
            foreach ($oldSequences as $oldSequence) {
                $oldContent[] = $oldSequence->getKey();
            }

            $newContent = $oldContent;
            $newContent[] = $sequence->getKey();
        } while (!$this->compareAndSet($oldContent, $newContent));
    }

    /**
     * Remove the first occurrence of the {@link Sequence} from this aggregate.
     *
     * @param Sequence $sequence to be removed from this aggregate.
     * @return bool true if the sequence was removed otherwise false.
     */
    public function remove(Sequence $sequence)
    {
        return SequenceGroups::removeSequence($this, $sequence);
    }

    /**
     * Get the size of the group.
     *
     * @return int the size of the group.
     */
    public function size()
    {
        return count($this->getSequences());
    }

    /**
     * Adds a sequence to the sequence group after threads have started to publish to
     * the Disruptor.  It will set the sequences to cursor value of the ringBuffer
     * just after adding them.  This should prevent any nasty rewind/wrapping effects.
     *
     * @param CursoredInterface $cursored The data structure that the owner of this sequence group will
     * be pulling it's events from.
     * @param Sequence[] $sequences The sequence to add.
     * @return void
     */
    public function addWhileRunning(CursoredInterface $cursored, array $sequences)
    {
        SequenceGroups::addSequences($this, $cursored, $sequences);
    }
}
