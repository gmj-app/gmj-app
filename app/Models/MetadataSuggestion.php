<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataSuggestion extends Model
{
    protected $fillable = ['creator_video_id', 'suggestion_type', 'suggested_subject_id', 'suggested_content_item_id', 'suggested_display_value', 'confidence', 'confidence_score', 'rule_name', 'evidence', 'status', 'source_fingerprint', 'reviewed_by_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['confidence_score' => 'decimal:4', 'evidence' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(CreatorVideo::class, 'creator_video_id');
    }

    public function suggestedSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'suggested_subject_id');
    }

    public function suggestedContentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'suggested_content_item_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
