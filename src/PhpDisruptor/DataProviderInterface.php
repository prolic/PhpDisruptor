<?php

namespace PhpDisruptor;

interface DataProviderInterface
{
    /**
     * @return string
     */
    public function getEventClass();

    /**
     * @param int $sequence
     * @return DataInterface
     */
    public function get($sequence);
}
