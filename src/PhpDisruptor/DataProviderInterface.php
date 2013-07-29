<?php

namespace PhpDisruptor;

interface DataProviderInterface extends EventClassCapableInterface
{
    /**
     * @param int $sequence
     * @return DataInterface
     */
    public function get($sequence);
}
