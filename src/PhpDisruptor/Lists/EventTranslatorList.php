<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Pthreads\StackableArray;

class EventTranslatorList extends StackableArray
{
    public $list;

    /**
     * @param EventTranslatorInterface|array $entities
     */
    public function __construct($entities)
    {
        if ($entities instanceof EventTranslatorInterface) {
            $this->add($entities);
        } else if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        }
    }

    public function add(EventTranslatorInterface $entity)
    {
        $this->list[] = $entity;
    }
}
