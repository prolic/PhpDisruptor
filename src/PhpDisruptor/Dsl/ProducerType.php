<?php

namespace PhpDisruptor\Dsl;

use Mabe_Enum as Enum;
use PhpDisruptor\Exception;

class ProducerType extends Enum
{
    const SINGLE = 0;
    const MULTI  = 1;
}
