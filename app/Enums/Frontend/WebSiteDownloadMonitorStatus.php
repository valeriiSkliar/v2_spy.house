<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum WebSiteDownloadMonitorStatus: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case IN_PROGRESS = 'in_progress';
    case CANCELLED = 'cancelled';
}
