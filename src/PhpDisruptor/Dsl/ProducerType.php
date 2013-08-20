<?php

namespace PhpDisruptor\Dsl;

use Mabe_Enum as Enum;

final class ProducerType extends Enum
{
    const SINGLE = 0;
    const MULTI  = 1;
}
