<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, SoftDeletes;

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_CREATORS = 'creators';

    public const AUDIENCES = [self::AUDIENCE_ALL, self::AUDIENCE_CREATORS];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHING = 'publishing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'internal_name', 'title', 'message', 'audience', 'action_url', 'action_label',
        'icon', 'severity', 'status', 'starts_at', 'expires_at', 'published_at',
        'created_by_user_id', 'updated_by_user_id', 'recipient_count', 'delivered_count', 'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
            'recipient_count' => 'integer',
            'delivered_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AnnouncementDelivery::class);
    }

    public function adminAuditLogs()
    {
        return $this->morphMany(SuperAdminAuditLog::class, 'auditable');
    }

    public function audienceLabel(): string
    {
        return $this->audience === self::AUDIENCE_CREATORS ? 'Creators only' : 'All users';
    }

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_SCHEDULED && $this->expires_at?->isPast()) {
            return 'Expired';
        }

        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true);
    }
}
