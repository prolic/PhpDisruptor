<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Exception;
use ConcurrentPhpUtils\NoOpStackable;
use Traversable;

class EventProcessorList extends NoOpStackable
{
    /**
     * Constructor
     *
     * @param AbstractEventProcessor|array|Traversable|null $entities
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($entities = null)
    {
        if ($entities instanceof AbstractEventProcessor) {
            $this->add($entities);
        } else if (is_array($entities) || $entities instanceof Traversable) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        } else if (null !== $entities) {
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
        $this[] = $entity;
    }
}
