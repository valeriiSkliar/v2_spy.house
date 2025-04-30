<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum PostType: string
{
    use EnumTrait;
    case Arbitration = 'arbitration';
    case Webmaster = 'webmaster';
    case About_service = 'about';
}
