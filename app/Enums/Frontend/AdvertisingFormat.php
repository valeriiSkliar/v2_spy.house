<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum AdvertisingFormat: string
{
    use EnumTrait;

    case PUSH = 'push';
    case ON_CLICK = 'onclick';
    case INPAGE = 'inpage';
    case NATIVE = 'native';
    case POP = 'pop';
}
