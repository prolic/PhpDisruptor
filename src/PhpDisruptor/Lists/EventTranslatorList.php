<?php

namespace PhpDisruptor\Lists;

use PhpDisruptor\EventTranslatorInterface;
use PhpDisruptor\Exception;
use ConcurrentPhpUtils\NoOpStackable;
use Traversable;

class EventTranslatorList extends NoOpStackable
{
    /**
     * Constructor
     *
     * @param EventTranslatorInterface|array|Traversable|null $entities
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($entities = null)
    {
        if ($entities instanceof EventTranslatorInterface) {
            $this->add($entities);
        } else if (is_array($entities) || $entities instanceof Traversable) {
            foreach ($entities as $entity) {
                $this->add($entity);
            }
        } else if (null !== $entities) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an %s, %s or %s',
                __METHOD__, 'array', 'Traversable', 'PhpDisruptor\EventTranslatorInterface'
            ));
        }
    }

    /**
     * @param EventTranslatorInterface $entity
     */
    public function add(EventTranslatorInterface $entity)
    {
        $this[] = $entity;
    }
}
