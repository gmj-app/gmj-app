<?php

namespace App\Enums;

enum ThumbnailLayoutStyle: string
{
    case CreatorLeftSubjectRight = 'creator_left_subject_right';
    case SubjectLeftCreatorRight = 'subject_left_creator_right';
    case CreatorCenter = 'creator_center';
    case SubjectCenter = 'subject_center';
    case SplitScreen = 'split_screen';
    case ThreePanel = 'three_panel';
    case TextDominant = 'text_dominant';
    case ImageDominant = 'image_dominant';
    case Other = 'other';
}
