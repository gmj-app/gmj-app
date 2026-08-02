<?php

namespace App\Enums;

enum PerformanceSnapshotSource: string
{
    case YouTubeStudio = 'youtube_studio';
    case Vidiq = 'vidiq';
    case Manual = 'manual';
    case Combined = 'combined';
}
