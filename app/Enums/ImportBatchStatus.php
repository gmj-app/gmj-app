<?php

namespace App\Enums;

enum ImportBatchStatus: string
{
    case Uploaded = 'uploaded';
    case Inspecting = 'inspecting';
    case AwaitingMapping = 'awaiting_mapping';
    case Ready = 'ready';
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
}
