<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationAlternative extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_DISMISSED,
    ];

    protected $fillable = [
        'recommendation_id',
        'user_id',
        'reviewed_by',
        'alternative_url',
        'alternative_video_id',
        'reason',
        'status',
        'accepted_at',
        'dismissed_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
