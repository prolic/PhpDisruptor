<?php

namespace PhpDisruptor\Dsl;

use Mabe_Enum as Enum;
use PhpDisruptor\Exception;

final class ProducerType extends Enum
{
    const SINGLE = 0;
    const MULTI  = 1;
}
