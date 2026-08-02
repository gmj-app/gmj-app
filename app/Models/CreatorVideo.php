<?php

namespace App\Models;

use App\Enums\MetadataCompletionStatus;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use Database\Factories\CreatorVideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CreatorVideo extends Model
{
    /** @use HasFactory<CreatorVideoFactory> */
    use HasFactory;

    protected $fillable = ['creator_channel_id', 'platform_video_id', 'title', 'description', 'video_url', 'thumbnail_url', 'published_at', 'duration_seconds', 'video_format', 'content_type', 'is_premiere', 'is_live', 'is_short', 'is_documentary', 'is_interview', 'is_monetized', 'copyright_status', 'content_item_not_applicable', 'content_item_not_applicable_by_user_id', 'content_item_not_applicable_at', 'metadata_completion_percentage', 'metadata_completion_status', 'metadata_completion_calculated_at'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'duration_seconds' => 'integer',
            'video_format' => VideoFormat::class,
            'content_type' => VideoContentType::class,
            'copyright_status' => VideoCopyrightStatus::class,
            'is_premiere' => 'boolean',
            'is_live' => 'boolean',
            'is_short' => 'boolean',
            'is_documentary' => 'boolean',
            'is_interview' => 'boolean',
            'is_monetized' => 'boolean',
            'content_item_not_applicable' => 'boolean',
            'content_item_not_applicable_at' => 'datetime',
            'metadata_completion_percentage' => 'integer',
            'metadata_completion_status' => MetadataCompletionStatus::class,
            'metadata_completion_calculated_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CreatorChannel::class, 'creator_channel_id');
    }

    public function performanceSnapshots(): HasMany
    {
        return $this->hasMany(VideoPerformanceSnapshot::class);
    }

    public function importRows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'creator_video_subject')->withPivot(['relationship_type', 'is_primary'])->withTimestamps();
    }

    public function primarySubject(): BelongsToMany
    {
        return $this->subjects()->wherePivot('is_primary', true);
    }

    public function contentItems(): BelongsToMany
    {
        return $this->belongsToMany(ContentItem::class, 'creator_video_content_item')->withPivot('is_primary')->withTimestamps();
    }

    public function primaryContentItem(): BelongsToMany
    {
        return $this->contentItems()->wherePivot('is_primary', true);
    }

    public function titleMetadata(): HasOne
    {
        return $this->hasOne(VideoTitleMetadata::class);
    }

    public function thumbnailMetadata(): HasOne
    {
        return $this->hasOne(VideoThumbnailMetadata::class);
    }

    public function editorialMetadata(): HasOne
    {
        return $this->hasOne(VideoEditorialMetadata::class);
    }

    public function metadataSuggestions(): HasMany
    {
        return $this->hasMany(MetadataSuggestion::class);
    }
}
