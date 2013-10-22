<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\Pthreads\StackableArray;
use Traversable;

class EventHandlerList extends StackableArray
{
    /**
     * @var array
     */
    public $list;

    /**
     * Constructor
     *
     * @param EventHandlerInterface|array|Traversable $entities
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($entities)
    {
        if ($entities instanceof EventHandlerInterface) {
            $this->add($entities);
        } else if (is_array($entities) || $entities instanceof Traversable) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        } else {
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
        $this->list[] = $entity;
    }
}
