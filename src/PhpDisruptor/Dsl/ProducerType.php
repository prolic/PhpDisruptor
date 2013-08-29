<?php

namespace PhpDisruptor\Dsl;

use MabeEnum_Enum as Enum;

final class ProducerType extends Enum
{
    const SINGLE = 0;
    const MULTI  = 1;
}
