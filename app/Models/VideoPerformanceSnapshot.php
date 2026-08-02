<?php

namespace App\Models;

use App\Enums\PerformanceSnapshotSource;
use Database\Factories\VideoPerformanceSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoPerformanceSnapshot extends Model
{
    /** @use HasFactory<VideoPerformanceSnapshotFactory> */
    use HasFactory;

    protected $fillable = ['creator_video_id', 'snapshot_date', 'source', 'views', 'impressions', 'impressions_ctr', 'watch_time_minutes', 'average_view_duration_seconds', 'average_percentage_viewed', 'likes', 'comments', 'shares', 'subscribers_gained', 'subscribers_lost', 'estimated_revenue', 'rpm', 'cpm', 'hype_points', 'views_first_24_hours', 'views_first_7_days', 'views_first_28_days'];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'source' => PerformanceSnapshotSource::class,
            'impressions_ctr' => 'decimal:4',
            'watch_time_minutes' => 'decimal:4',
            'average_percentage_viewed' => 'decimal:4',
            'estimated_revenue' => 'decimal:4',
            'rpm' => 'decimal:4',
            'cpm' => 'decimal:4',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(CreatorVideo::class, 'creator_video_id');
    }
}
