<?php

namespace App\Enums;

enum VideoFormat: string
{
    case Long = 'long';
    case Short = 'short';
    case Live = 'live';
    case Unknown = 'unknown';
}
