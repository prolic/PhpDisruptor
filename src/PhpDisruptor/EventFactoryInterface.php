<?php

namespace PhpDisruptor;

interface EventFactoryInterface
{
    /**
     * @return string
     */
    public function getEventClass();

    /*
     * Implementations should instantiate an event object, with all memory already allocated where possible.
     *
     * @return EventInterface
     */
    public function newInstance();
}
