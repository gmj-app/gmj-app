<?php

namespace App\Models;

use App\Enums\TitleTemplate;
use Database\Factories\VideoTitleMetadataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoTitleMetadata extends Model
{
    /** @use HasFactory<VideoTitleMetadataFactory> */
    use HasFactory;

    protected $table = 'video_title_metadata';

    protected $fillable = ['creator_video_id', 'character_count', 'word_count', 'contains_question', 'contains_exclamation', 'contains_pipe', 'contains_parentheses', 'contains_all_caps', 'subject_name_present', 'content_item_name_present', 'negative_hook', 'curiosity_hook', 'emotional_hook', 'controversy_hook', 'technical_hook', 'discovery_hook', 'title_template', 'notes', 'classified_by_user_id', 'classified_at', 'reviewed_by_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['contains_question' => 'boolean', 'contains_exclamation' => 'boolean', 'contains_pipe' => 'boolean', 'contains_parentheses' => 'boolean', 'contains_all_caps' => 'boolean', 'subject_name_present' => 'boolean', 'content_item_name_present' => 'boolean', 'negative_hook' => 'boolean', 'curiosity_hook' => 'boolean', 'emotional_hook' => 'boolean', 'controversy_hook' => 'boolean', 'technical_hook' => 'boolean', 'discovery_hook' => 'boolean', 'title_template' => TitleTemplate::class, 'classified_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(CreatorVideo::class, 'creator_video_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
