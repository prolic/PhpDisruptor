<?php

namespace PhpDisruptor;

interface EventFactoryInterface extends EventClassCapableInterface
{
    /*
     * Implementations should instantiate an event object, with all memory already allocated where possible.
     *
     * @return object
     */
    public function newInstance();
}
