<?php

namespace App\Enums;

enum TitleTemplate: string
{
    case ReactionFirst = 'reaction_first';
    case SubjectFirst = 'subject_first';
    case ContentItemFirst = 'content_item_first';
    case CuriosityStatement = 'curiosity_statement';
    case NegativeHonesty = 'negative_honesty';
    case QuestionHook = 'question_hook';
    case TechnicalPraise = 'technical_praise';
    case EmotionalReaction = 'emotional_reaction';
    case Discovery = 'discovery';
    case Comparison = 'comparison';
    case Descriptive = 'descriptive';
    case Other = 'other';
}
