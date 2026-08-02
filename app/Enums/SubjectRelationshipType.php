<?php

namespace App\Enums;

enum SubjectRelationshipType: string
{
    case Primary = 'primary';
    case Featured = 'featured';
    case Collaboration = 'collaboration';
    case Comparison = 'comparison';
    case Mentioned = 'mentioned';
}
