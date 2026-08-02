<?php

namespace App\Enums;

enum VideoCopyrightStatus: string
{
    case Clear = 'clear';
    case Claimed = 'claimed';
    case Blocked = 'blocked';
    case Demonetized = 'demonetized';
    case Unknown = 'unknown';
}
