<?php

namespace PhpDisruptor;

use PhpDisruptor\Util\Util;
use Zend\Cache\Storage\StorageInterface;

class SequenceGroup extends Sequence
{
    /**
     * Constructor
     *
     * @param StorageInterface $storage
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
    protected function sequences()
    {
        $sequences = array();
        $content = $this->storage->getItem($this->key);
        foreach ($content as $sequence) {
            $sequences[] = Sequence::fromKey($this->storage, $sequence);
        }
        return $sequences;
    }

    /**
     * Get the minimum sequence value for the group.
     *
     * @return int the minimum sequence value for the group.
     */
    public function get()
    {
        return Util::getMinimumSequence($this->sequences());
    }

    /**
     * Set all {@link Sequence}s in the group to a given value.
     *
     * @param int $value to set the group of sequences to.
     * @throws Exception\ExceptionInterface
     */
    public function set($value)
    {
        if (!is_numeric($value)) {
            throw new Exception\InvalidArgumentException('value must be an integer');
        }
        $sequences = $this->sequences();
        foreach ($sequences as $sequence) {
            $sequence->set($value);
        }
    }

    /**
     * Add a {@link Sequence} into this aggregate.  This should only be used during
     * initialisation.  Use {@link SequenceGroup#addWhileRunning(Cursored, Sequence)}
     *
     * @see SequenceGroup#addWhileRunning(Cursored, Sequence)
     * @param Sequence $sequence to be added to the aggregate.
     */
    public function add(Sequence $sequence)
    {
        do {
            $oldSequences = $this->sequences();

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
        $found = false;
        do {
            $oldSequences = $this->sequences();
            $newSequences = array();
            $oldContent = array();
            $newContent = array();
            foreach ($oldSequences as $oldSequence) {
                $oldContent[] = $oldSequence->getKey();
                if ($oldSequence->equals($sequence)) {
                    $found = true;
                } else {
                    $newContent[] = $oldSequence->getKey();
                }
            }
        } while (!$this->compareAndSet($oldContent, $newContent));
        return $found;
    }

    /**
     * Get the size of the group.
     *
     * @return int the size of the group.
     */
    public function size()
    {
        return count($this->sequences());
    }

    /**
     * Adds a sequence to the sequence group after threads have started to publish to
     * the Disruptor.  It will set the sequences to cursor value of the ringBuffer
     * just after adding them.  This should prevent any nasty rewind/wrapping effects.
     *
     * @param CursoredInterface $cursored The data structure that the owner of this sequence group will
     * be pulling it's events from.
     * @param Sequence $sequence The sequence to add.
     * @return void
     */
    public function addWhileRunning(CursoredInterface $cursored, Sequence $sequence)
    {
        //SequenceGroups.addSequences(this, SEQUENCE_UPDATER, cursored, sequence);
    }
}
