<?php

namespace App\Models;

use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use Database\Factories\CreatorVideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorVideo extends Model
{
    /** @use HasFactory<CreatorVideoFactory> */
    use HasFactory;

    protected $fillable = ['creator_channel_id', 'platform_video_id', 'title', 'description', 'video_url', 'thumbnail_url', 'published_at', 'duration_seconds', 'video_format', 'content_type', 'is_premiere', 'is_live', 'is_short', 'is_documentary', 'is_interview', 'is_monetized', 'copyright_status'];

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
}
