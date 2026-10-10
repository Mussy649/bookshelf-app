<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case InProgress = 'in_progress';
    case Expired = 'expired';
    case Completed = 'completed';
}
