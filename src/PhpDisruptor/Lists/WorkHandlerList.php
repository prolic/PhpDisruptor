<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\Exception;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\WorkHandlerInterface;
use Traversable;

class WorkHandlerList extends StackableArray
{
    /**
     * @var array
     */
    public $list;

    /**
     * Constructor
     *
     * @param WorkHandlerInterface|array|Traversable $entities
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($entities)
    {
        if ($entities instanceof WorkHandlerInterface) {
            $this->add($entities);
        } else if (is_array($entities) || $entities instanceof Traversable) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an %s, %s or %s',
                __METHOD__, 'array', 'Traversable', 'PhpDisruptor\WorkHandlerInterface'
            ));
        }
    }

    /**
     * @param WorkHandlerInterface $entity
     */
    public function add(WorkHandlerInterface $entity)
    {
        $this->list[] = $entity;
    }
}
