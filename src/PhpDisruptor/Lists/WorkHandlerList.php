<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\WorkHandlerInterface;

class WorkHandlerList extends StackableArray
{
    public $list;

    /**
     * @param WorkHandlerInterface|array $entities
     */
    public function __construct($entities)
    {
        if ($entities instanceof WorkHandlerInterface) {
            $this->add($entities);
        } else if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        }
    }

    public function add(WorkHandlerInterface $entity)
    {
        $this->list[] = $entity;
    }
}
