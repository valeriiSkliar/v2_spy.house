<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum AdvertisingFormat: string
{
    use EnumTrait;

    case PUSH = 'push';
    case INPAGE = 'inpage';

    case TIKTOK = 'tiktok';
    case FACEBOOK = 'facebook';
}
