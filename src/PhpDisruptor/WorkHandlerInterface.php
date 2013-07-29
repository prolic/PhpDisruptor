<?php

namespace PhpDisruptor;

interface WorkHandler extends EventClassCapableInterface
{
    /**
     * @param object $event
     * @return void
     * @throws \Exception
     */
    public function onEvent($event);
}
