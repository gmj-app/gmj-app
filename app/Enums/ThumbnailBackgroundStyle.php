<?php

namespace App\Enums;

enum ThumbnailBackgroundStyle: string
{
    case Dark = 'dark';
    case Light = 'light';
    case White = 'white';
    case Concert = 'concert';
    case Performance = 'performance';
    case Studio = 'studio';
    case Blurred = 'blurred';
    case Gradient = 'gradient';
    case Cutout = 'cutout';
    case Collage = 'collage';
    case Other = 'other';
}
