<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\Sequence as Seq;
use PhpDisruptor\Pthreads\StackableArray;

class Sequence extends StackableArray
{
    public $list;

    /**
     * @param Seq|array $entities
     */
    public function __construct($entities)
    {
        if ($entities instanceof Seq) {
            $this->add($entities);
        } else if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        }
    }

    public function add(Seq $entity)
    {
        $this->list[] = $entity;
    }
}
