<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\Exception;
use PhpDisruptor\Sequence;
use Threaded;
use Traversable;

class SequenceList extends Threaded
{
    /**
     * Constructor
     *
     * @param Sequence|array|Traversable|null $entities
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($entities = null)
    {
        if ($entities instanceof Sequence) {
            $this->add($entities);
        } else if (is_array($entities) || $entities instanceof Traversable) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        } else if (null !== $entities) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an %s, %s or %s',
                __METHOD__, 'array', 'Traversable', 'PhpDisruptor\Sequence'
            ));
        }
    }

    /**
     * @param Sequence $entity
     */
    public function add(Sequence $entity)
    {
        $this[] = $entity;
    }
}
