<?php

namespace App\Enums;

enum ImportBatchSource: string
{
    case YouTubeStudio = 'youtube_studio';
    case Vidiq = 'vidiq';
    case Manual = 'manual';
}
