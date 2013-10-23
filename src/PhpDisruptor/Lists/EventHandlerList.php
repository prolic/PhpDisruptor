<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\Pthreads\StackableArray;
use Traversable;

class EventHandlerList extends StackableArray
{
    /**
     * Constructor
     *
     * @param EventHandlerInterface|array|Traversable|null $entities
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($entities = null)
    {
        if ($entities instanceof EventHandlerInterface) {
            $this->add($entities);
        } else if (is_array($entities) || $entities instanceof Traversable) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        } else if (null !== $entities) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an %s, %s or %s',
                __METHOD__, 'array', 'Traversable', 'PhpDisruptor\EventHandlerInterface'
            ));
        }
    }

    /**
     * @param EventHandlerInterface $entity
     */
    public function add(EventHandlerInterface $entity)
    {
        $this[] = $entity;
    }
}
