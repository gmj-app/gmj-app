<?php

namespace App\Enums;

enum MetadataCompletionStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Complete = 'complete';
}
