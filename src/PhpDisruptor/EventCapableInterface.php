<?php

namespace PhpDisruptor;

interface EventClassCapableInterface
{
    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass();
}
