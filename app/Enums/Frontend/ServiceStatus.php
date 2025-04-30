<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum ServiceStatus: string
{
    use EnumTrait;

    case ACTIVE = 'Active';
    case INACTIVE = 'Inactive';
}
