<?php

namespace App\Enums;

enum MetadataScale: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
