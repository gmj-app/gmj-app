<?php

namespace App\Models;

use Database\Factories\RecommendationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
        'withdrawn',
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
        'withdrawn' => 'Withdrawn',
        'planned' => 'Coming Soon',
        'declined' => 'Passed',
    ];

    protected $fillable = [
        'creator_id',
        'submitted_by',
        'submission_source',
        'recommendation_type',
        'media_type',
        'youtube_url',
        'normalized_url',
        'youtube_video_id',
        'youtube_playlist_id',
        'channel_title',
        'thumbnail_url',
        'source_title',
        'source_channel',
        'source_item_count',
        'source_metadata',
        'title',
        'artist',
        'category',
        'description',
        'reason',
        'status',
        'resource_released_at',
        'resource_release_reason',
        'is_pinned',
        'scheduled_for',
        'published_at',
        'moderation_reason',
        'moderation_note',
        'moderated_by',
        'moderated_at',
        'published_reaction_url',
        'published_normalized_url',
        'published_media_type',
        'published_title',
        'published_channel',
        'published_thumbnail_url',
        'published_video_id',
        'published_playlist_id',
        'published_item_count',
        'published_metadata',
        'withdrawn_at',
        'withdrawn_by_user_id',
        'withdrawal_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
            'published_metadata' => 'array',
            'source_metadata' => 'array',
            'withdrawn_at' => 'datetime',
            'resource_released_at' => 'datetime',
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

    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by_user_id');
    }

    public function userPicks(): HasMany
    {
        return $this->hasMany(UserPick::class)->whereNull('released_at');
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

    public function displayThumbnailUrl(): ?string
    {
        return filled($this->thumbnail_url)
            ? (string) $this->thumbnail_url
            : $this->youtubeThumbnailUrl();
    }

    public function isYouTubePlaylist(): bool
    {
        return $this->media_type === 'playlist' && filled($this->youtube_playlist_id);
    }

    public function isYouTubeVideo(): bool
    {
        return ! $this->isYouTubePlaylist() && filled($this->youtube_video_id);
    }

    public function canonicalMediaUrl(): ?string
    {
        return filled($this->normalized_url) ? (string) $this->normalized_url : $this->youtube_url;
    }

    public function displaySourceTitle(): string
    {
        return filled($this->source_title) ? (string) $this->source_title : (string) $this->title;
    }

    public function displaySourceChannel(): ?string
    {
        return filled($this->source_channel)
            ? (string) $this->source_channel
            : (filled($this->channel_title) ? (string) $this->channel_title : null);
    }

    public function displayItemCount(): ?int
    {
        return $this->source_item_count === null ? null : (int) $this->source_item_count;
    }

    public function mediaTypeLabel(): string
    {
        return match (true) {
            $this->isYouTubePlaylist() => 'Playlist',
            $this->recommendation_type === 'topic' => 'Topic',
            $this->isYouTubeVideo() => 'YouTube',
            default => 'Link',
        };
    }

    public function isPublishedYouTubePlaylist(): bool
    {
        return ($this->published_media_type === 'playlist' && filled($this->published_playlist_id))
            || (blank($this->published_reaction_url) && $this->isYouTubePlaylist());
    }

    public function isTopicOnly(): bool
    {
        return $this->recommendation_type === 'topic'
            && blank($this->youtube_url)
            && $this->youtubeThumbnailUrl() === null;
    }

    public function hasMediaPreview(): bool
    {
        return $this->isYouTubePlaylist() || ! $this->isTopicOnly();
    }

    public function hasPublishedMediaPreview(): bool
    {
        return filled($this->displayPublishedUrl())
            || filled($this->displayPublishedThumbnailUrl());
    }

    public function displayPublishedTitle(): string
    {
        return filled($this->published_title)
            ? (string) $this->published_title
            : $this->displaySourceTitle();
    }

    public function displayPublishedChannel(): ?string
    {
        if (filled($this->published_channel)) {
            return (string) $this->published_channel;
        }

        if (filled($this->source_channel)) {
            return (string) $this->source_channel;
        }

        if (filled($this->channel_title)) {
            return (string) $this->channel_title;
        }

        return filled($this->artist) ? (string) $this->artist : null;
    }

    public function displayPublishedThumbnailUrl(): ?string
    {
        if ($this->isPublishedYouTubePlaylist()) {
            return filled($this->published_thumbnail_url) ? (string) $this->published_thumbnail_url : null;
        }

        return filled($this->published_thumbnail_url)
            ? (string) $this->published_thumbnail_url
            : $this->displayThumbnailUrl();
    }

    public function displayPublishedUrl(): ?string
    {
        if (filled($this->published_reaction_url)) {
            return (string) $this->published_reaction_url;
        }

        return filled($this->youtube_url) ? (string) $this->youtube_url : null;
    }

    public function displayPublishedDate(): ?Carbon
    {
        return $this->published_at ?? $this->updated_at ?? $this->created_at;
    }

    public function hasPublishedUrl(): bool
    {
        return filled($this->published_reaction_url);
    }

    /**
     * @return array{title: string, channel: string|null, thumbnail_url: string|null, url: string|null, has_published_url: bool, date: Carbon|null}
     */
    public function publishedDisplayData(): array
    {
        return [
            'title' => $this->displayPublishedTitle(),
            'channel' => $this->displayPublishedChannel(),
            'thumbnail_url' => $this->displayPublishedThumbnailUrl(),
            'url' => $this->displayPublishedUrl(),
            'has_published_url' => $this->hasPublishedUrl(),
            'date' => $this->displayPublishedDate(),
            'media_type' => $this->published_media_type ?: ($this->media_type ?: 'video'),
            'item_count' => $this->published_item_count ?? $this->source_item_count,
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status]
            ?? str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'already_seen' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            'coming_soon' => 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            'scheduled' => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
            'recorded' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            'passed', 'withdrawn' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            'hidden' => 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300',
            default => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
        };
    }

    public function totalVotes(): int
    {
        if (array_key_exists('user_picks_count', $this->attributes)) {
            return (int) $this->attributes['user_picks_count'];
        }

        if ($this->relationLoaded('userPicks')) {
            return (int) $this->userPicks->sum('vote_count');
        }

        return (int) $this->userPicks()->sum('vote_count');
    }

    public function currentUserVoteCount(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        if (array_key_exists('current_user_votes_count', $this->attributes)) {
            return (int) $this->attributes['current_user_votes_count'];
        }

        if ($this->relationLoaded('userPicks')) {
            return (int) $this->userPicks
                ->where('user_id', $user->id)
                ->sum('vote_count');
        }

        return (int) $this->userPicks()
            ->where('user_id', $user->id)
            ->sum('vote_count');
    }

    public function votedBy(?User $user): bool
    {
        return $this->currentUserVoteCount($user) > 0;
    }

    public function submittedByCurrentUser(?User $user): bool
    {
        return $user !== null
            && $this->submitted_by !== null
            && (int) $this->submitted_by === (int) $user->id;
    }

    public static function upvoteConsumingStatuses(): array
    {
        return self::UPVOTE_CONSUMING_STATUSES;
    }

    public static function votableStatuses(): array
    {
        return self::upvoteConsumingStatuses();
    }

    /**
     * @param  Builder<Recommendation>  $query
     * @return Builder<Recommendation>
     */
    public function scopeVotable(Builder $query): Builder
    {
        return $query->whereIn('status', self::votableStatuses());
    }

    /** @param Builder<Recommendation> $query */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->whereHas('creator', fn (Builder $query) => $query->availableForGuides());
    }

    /** @param Builder<Recommendation> $query */
    public function scopeActivePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::ACTIVE_PUBLIC_STATUSES)
            ->whereHas('creator', fn (Builder $query) => $query->availableForGuides());
    }

    public static function unfavoriteRemovableStatuses(): array
    {
        return self::UNFAVORITE_REMOVABLE_STATUSES;
    }

    public static function activePublicStatuses(): array
    {
        return self::ACTIVE_PUBLIC_STATUSES;
    }

    public static function suggestionConsumingStatuses(): array
    {
        return [
            'pending',
            'approved',
            'coming_soon',
            'scheduled',
            'recorded',
        ];
    }

    public static function suggesterRemovableStatuses(): array
    {
        return [
            'pending',
            'approved',
        ];
    }

    public static function statusConsumesUpvotes(string $status): bool
    {
        return in_array($status, self::votableStatuses(), true);
    }

    public function consumesUpvotes(): bool
    {
        return self::statusConsumesUpvotes($this->status);
    }

    public function isVotable(): bool
    {
        return $this->consumesUpvotes();
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, self::PUBLIC_STATUSES, true)
            && $this->creator?->isAvailableForGuides() === true;
    }

    public function isVotingClosed(): bool
    {
        return ! $this->isVotable();
    }

    public function canBeWithdrawnBy(?User $user): bool
    {
        return $user !== null
            && (int) $this->submitted_by === (int) $user->id
            && $this->submission_source === self::SUBMISSION_SOURCE_FAN
            && in_array($this->status, self::suggesterRemovableStatuses(), true);
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
