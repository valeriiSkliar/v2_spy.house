<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum AdvertisingStatus: string
{
    use EnumTrait;

    case Active = 'active';
    case Inactive = 'inactive';
    case Paused = 'paused';
}
