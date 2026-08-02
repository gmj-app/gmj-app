<?php

namespace App\Enums;

enum CreatorExpression: string
{
    case Shocked = 'shocked';
    case Excited = 'excited';
    case Laughing = 'laughing';
    case Serious = 'serious';
    case Confused = 'confused';
    case Emotional = 'emotional';
    case Angry = 'angry';
    case Disappointed = 'disappointed';
    case Pleading = 'pleading';
    case Neutral = 'neutral';
    case Other = 'other';
}
