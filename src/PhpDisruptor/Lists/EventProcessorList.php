<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Exception;
use PhpDisruptor\Pthreads\StackableArray;
use Traversable;

class EventProcessorList extends StackableArray
{
    /**
     * @var array
     */
    public $list;

    /**
     * Constructor
     *
     * @param AbstractEventProcessor|array|Traversable $entities
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($entities)
    {
        if ($entities instanceof AbstractEventProcessor) {
            $this->add($entities);
        } else if (is_array($entities) || $entities instanceof Traversable) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an %s, %s or %s',
                __METHOD__, 'array', 'Traversable', 'PhpDisruptor\EventProcessor\AbstractEventProcessor'
            ));
        }
    }

    /**
     * @param AbstractEventProcessor $entity
     */
    public function add(AbstractEventProcessor $entity)
    {
        $this->list[] = $entity;
    }
}
