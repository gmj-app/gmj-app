<?php

namespace App\Models;

use Database\Factories\RecommendationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recommendation extends Model
{
    /** @use HasFactory<RecommendationFactory> */
    use HasFactory;

    public const STATUSES = [
        'pending',
        'approved',
        'coming_soon',
        'scheduled',
        'recorded',
        'published',
        'already_seen',
        'passed',
        'hidden',
    ];

    public const PUBLIC_STATUSES = [
        'approved',
        'coming_soon',
        'scheduled',
        'recorded',
        'published',
        'already_seen',
        'passed',
    ];

    public const ACTIVE_PUBLIC_STATUSES = [
        'approved',
        'coming_soon',
        'scheduled',
        'recorded',
        'already_seen',
        'passed',
    ];

    public const UPVOTE_CONSUMING_STATUSES = [
        'pending',
        'approved',
    ];

    public const VISIBLE_STATUSES = self::PUBLIC_STATUSES;

    public const SUBMISSION_SOURCE_FAN = 'fan';

    public const SUBMISSION_SOURCE_CREATOR = 'creator';

    public const CATEGORY_OPTIONS = [
        'music',
        'documentary',
        'culture',
        'interview',
        'other',
    ];

    public const STATUS_LABELS = [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'coming_soon' => 'Coming Soon',
        'scheduled' => 'Scheduled',
        'recorded' => 'Recorded',
        'published' => 'Published',
        'already_seen' => 'Already Seen',
        'passed' => 'Passed',
        'hidden' => 'Hidden',
        'planned' => 'Coming Soon',
        'declined' => 'Passed',
    ];

    protected $fillable = [
        'creator_id',
        'submitted_by',
        'submission_source',
        'recommendation_type',
        'youtube_url',
        'youtube_video_id',
        'channel_title',
        'title',
        'artist',
        'category',
        'description',
        'reason',
        'status',
        'is_pinned',
        'scheduled_for',
        'published_at',
        'moderation_reason',
        'moderation_note',
        'moderated_by',
        'moderated_at',
        'published_reaction_url',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function userPicks(): HasMany
    {
        return $this->hasMany(UserPick::class);
    }

    public function creatorTags(): BelongsToMany
    {
        return $this->belongsToMany(CreatorTag::class, 'recommendation_tag')
            ->withTimestamps();
    }

    public function youtubeThumbnailUrl(): ?string
    {
        $videoId = (string) $this->youtube_video_id;

        if (preg_match('/\A[A-Za-z0-9_-]{11}\z/', $videoId) !== 1) {
            return null;
        }

        return "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status]
            ?? str($this->status)->replace('_', ' ')->title()->toString();
    }

    public static function upvoteConsumingStatuses(): array
    {
        return self::UPVOTE_CONSUMING_STATUSES;
    }

    public static function activePublicStatuses(): array
    {
        return self::ACTIVE_PUBLIC_STATUSES;
    }

    public static function statusConsumesUpvotes(string $status): bool
    {
        return in_array($status, self::UPVOTE_CONSUMING_STATUSES, true);
    }

    public function consumesUpvotes(): bool
    {
        return self::statusConsumesUpvotes($this->status);
    }

    public function isCreatorAdded(): bool
    {
        return $this->submission_source === self::SUBMISSION_SOURCE_CREATOR;
    }

    public function shouldClearUpvotesWhenStatusIs(string $status): bool
    {
        return $status !== $this->status
            && $this->consumesUpvotes()
            && ! self::statusConsumesUpvotes($status);
    }
}
