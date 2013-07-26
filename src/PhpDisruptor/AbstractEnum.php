<?php

namespace PhpDisruptor;

use ReflectionClass;

abstract class AbstractEnum
{
    protected $value;

    final public function __construct($value)
    {
        $c = new ReflectionClass($this);
        if(!in_array($value, $c->getConstants())) {
            throw new Exception\InvalidArgumentException();
        }
        $this->value = $value;
    }

    final public function __toString()
    {
        return $this->value;
    }
}
