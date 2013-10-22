<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Pthreads\StackableArray;

class EventHandlerList extends StackableArray
{
    public $list;

    /**
     * @param EventHandlerInterface|array $entities
     */
    public function __construct($entities)
    {
        if ($entities instanceof EventHandlerInterface) {
            $this->add($entities);
        } else if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        }
    }

    public function add(EventHandlerInterface $entity)
    {
        $this->list[] = $entity;
    }
}
