<?php

namespace App\Enums;

enum ReactionStyle: string
{
    case Technical = 'technical';
    case Emotional = 'emotional';
    case Comedic = 'comedic';
    case Cultural = 'cultural';
    case Educational = 'educational';
    case Critical = 'critical';
    case Discovery = 'discovery';
    case Mixed = 'mixed';
}
