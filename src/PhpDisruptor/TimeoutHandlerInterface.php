<?php

namespace PhpDisruptor;

interface TimeoutHandlerInterface
{
    /**
     * @param int $sequence
     * @return void
     * @throws Exception\ExceptionInterface
     */
    public function onTimeout($sequence);
}
