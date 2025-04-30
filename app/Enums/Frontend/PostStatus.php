<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum PostStatus: string
{
    use EnumTrait;

    case Published = 'Published';
    case Unpublished = 'Unpublished';
    case Draft = 'Draft';
}
