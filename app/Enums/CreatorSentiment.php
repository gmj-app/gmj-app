<?php

namespace App\Enums;

enum CreatorSentiment: string
{
    case Loved = 'loved';
    case Positive = 'positive';
    case Mixed = 'mixed';
    case Negative = 'negative';
    case Disliked = 'disliked';
    case Neutral = 'neutral';
    case Unknown = 'unknown';
}
