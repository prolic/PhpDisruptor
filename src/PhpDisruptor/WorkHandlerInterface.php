<?php

namespace PhpDisruptor;

interface WorkHandlerInterface extends EventClassCapableInterface
{
    /**
     * @param object $event
     * @return void
     * @throws \Exception
     */
    public function onEvent($event);
}
