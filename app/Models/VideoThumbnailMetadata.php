<?php

namespace App\Models;

use App\Enums\CreatorExpression;
use App\Enums\ThumbnailBackgroundStyle;
use App\Enums\ThumbnailLayoutStyle;
use App\Enums\ThumbnailTextPosition;
use Database\Factories\VideoThumbnailMetadataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoThumbnailMetadata extends Model
{
    /** @use HasFactory<VideoThumbnailMetadataFactory> */
    use HasFactory;

    protected $table = 'video_thumbnail_metadata';

    protected $fillable = ['creator_video_id', 'thumbnail_version', 'text_content', 'text_word_count', 'face_count', 'creator_face_visible', 'subject_face_visible', 'creator_expression', 'background_style', 'dominant_color_label', 'layout_style', 'contains_question', 'contains_arrow', 'contains_circle', 'contains_flag', 'contains_logo', 'text_position', 'notes', 'classified_by_user_id', 'classified_at', 'reviewed_by_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['creator_face_visible' => 'boolean', 'subject_face_visible' => 'boolean', 'contains_question' => 'boolean', 'contains_arrow' => 'boolean', 'contains_circle' => 'boolean', 'contains_flag' => 'boolean', 'contains_logo' => 'boolean', 'creator_expression' => CreatorExpression::class, 'background_style' => ThumbnailBackgroundStyle::class, 'layout_style' => ThumbnailLayoutStyle::class, 'text_position' => ThumbnailTextPosition::class, 'classified_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(CreatorVideo::class, 'creator_video_id');
    }
}
