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

    public const UNFAVORITE_REMOVABLE_STATUSES = [
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
        'published_title',
        'published_channel',
        'published_thumbnail_url',
        'published_video_id',
        'published_metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
            'published_metadata' => 'array',
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

    public function alternatives(): HasMany
    {
        return $this->hasMany(RecommendationAlternative::class);
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

    public function displayPublishedTitle(): string
    {
        return filled($this->published_title)
            ? (string) $this->published_title
            : (string) $this->title;
    }

    public function displayPublishedChannel(): ?string
    {
        if (filled($this->published_channel)) {
            return (string) $this->published_channel;
        }

        if (filled($this->channel_title)) {
            return (string) $this->channel_title;
        }

        return filled($this->artist) ? (string) $this->artist : null;
    }

    public function displayPublishedThumbnailUrl(): ?string
    {
        return filled($this->published_thumbnail_url)
            ? (string) $this->published_thumbnail_url
            : $this->youtubeThumbnailUrl();
    }

    public function displayPublishedUrl(): ?string
    {
        if (filled($this->published_reaction_url)) {
            return (string) $this->published_reaction_url;
        }

        return filled($this->youtube_url) ? (string) $this->youtube_url : null;
    }

    public function hasPublishedUrl(): bool
    {
        return filled($this->published_reaction_url);
    }

    /**
     * @return array{title: string, channel: string|null, thumbnail_url: string|null, url: string|null, has_published_url: bool}
     */
    public function publishedDisplayData(): array
    {
        return [
            'title' => $this->displayPublishedTitle(),
            'channel' => $this->displayPublishedChannel(),
            'thumbnail_url' => $this->displayPublishedThumbnailUrl(),
            'url' => $this->displayPublishedUrl(),
            'has_published_url' => $this->hasPublishedUrl(),
        ];
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

    public static function unfavoriteRemovableStatuses(): array
    {
        return self::UNFAVORITE_REMOVABLE_STATUSES;
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
