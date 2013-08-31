<?php

namespace PhpDisruptor;

use Exception;

interface WorkHandlerInterface extends EventClassCapableInterface
{
    /**
     * @param object $event
     * @return void
     * @throws Exception
     */
    public function onEvent($event);
}
