<?php

namespace PhpDisruptor;

interface DataProviderInterface
{
    /**
     * @return string
     */
    public function getDataClass();

    /**
     * @param int $sequence
     * @return DataInterface
     */
    public function get($sequence);
}
