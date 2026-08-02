<?php

namespace App\Enums;

enum VideoContentType: string
{
    case Reaction = 'reaction';
    case Review = 'review';
    case Interview = 'interview';
    case Documentary = 'documentary';
    case Livestream = 'livestream';
    case Vlog = 'vlog';
    case Educational = 'educational';
    case Other = 'other';
}
