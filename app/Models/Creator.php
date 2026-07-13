<?php

namespace App\Models;

use App\Services\CreatorLifecycleService;
use Database\Factories\CreatorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Creator extends Model
{
    /** @use HasFactory<CreatorFactory> */
    use HasFactory;

    use SoftDeletes;

    public const APPROVAL_MODE_MANUAL = 'manual';

    public const APPROVAL_MODE_AUTO = 'auto';

    public const RECOMMENDATION_APPROVAL_MODES = [
        self::APPROVAL_MODE_MANUAL,
        self::APPROVAL_MODE_AUTO,
    ];

    protected $attributes = [
        'submissions_open' => true,
        'recommendation_approval_mode' => self::APPROVAL_MODE_AUTO,
        'status' => 'active',
    ];

    protected $fillable = [
        'slug',
        'display_name',
        'channel_url',
        'youtube_channel_id',
        'youtube_channel_title',
        'youtube_channel_url',
        'youtube_thumbnail_url',
        'avatar_path',
        'youtube_banner_url',
        'hero_path',
        'verification_status',
        'verified_at',
        'bio',
        'submission_instructions',
        'submissions_open',
        'recommendation_approval_mode',
        'status',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'submissions_open' => 'boolean',
            'deactivated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (Creator $creator): void {
            if ($creator->wasChanged(['status', 'deactivated_at']) && ! $creator->isAvailableForGuides()) {
                app(CreatorLifecycleService::class)->releaseResources($creator);
            }
        });

        static::deleted(fn (Creator $creator) => app(CreatorLifecycleService::class)->releaseResources($creator));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailableForGuides(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNull('deactivated_at');
    }

    public function isAvailableForGuides(): bool
    {
        return ! $this->trashed()
            && $this->status === 'active'
            && $this->deactivated_at === null;
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->display_name), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) >= 2) {
            return Str::upper(Str::substr($words[0], 0, 1).Str::substr($words[1], 0, 1));
        }

        if (count($words) === 1) {
            return Str::upper(Str::substr($words[0], 0, 2));
        }

        return 'G';
    }

    public function getCardDescriptionAttribute(): string
    {
        $description = filled($this->bio)
            ? $this->bio
            : $this->submission_instructions;

        if (blank($description)) {
            return "Help guide this creator's journey.";
        }

        $description = preg_replace('/\s+/', ' ', strip_tags((string) $description));

        return Str::limit(trim((string) $description), 120);
    }

    public function autoApprovesRecommendations(): bool
    {
        return $this->recommendation_approval_mode === self::APPROVAL_MODE_AUTO;
    }

    public function defaultRecommendationStatus(): string
    {
        return $this->autoApprovesRecommendations() ? 'approved' : 'pending';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        // Creator uploads take precedence over future YouTube imports.
        if ($this->hasUploadedAvatar()) {
            return filter_var($this->avatar_path, FILTER_VALIDATE_URL)
                ? $this->avatar_path
                : Storage::disk(config('filesystems.default'))->url($this->avatar_path);
        }

        return filled($this->youtube_thumbnail_url) ? $this->youtube_thumbnail_url : null;
    }

    public function getHeroUrlAttribute(): ?string
    {
        // Creator uploads take precedence over future YouTube imports.
        if ($this->hasUploadedHero()) {
            return filter_var($this->hero_path, FILTER_VALIDATE_URL)
                ? $this->hero_path
                : Storage::disk(config('filesystems.default'))->url($this->hero_path);
        }

        return filled($this->youtube_banner_url) ? $this->youtube_banner_url : null;
    }

    public function hasUploadedAvatar(): bool
    {
        if (blank($this->avatar_path)) {
            return false;
        }

        return filter_var($this->avatar_path, FILTER_VALIDATE_URL)
            || Storage::disk(config('filesystems.default'))->exists($this->avatar_path);
    }

    public function hasUploadedHero(): bool
    {
        if (blank($this->hero_path)) {
            return false;
        }

        return filter_var($this->hero_path, FILTER_VALIDATE_URL)
            || Storage::disk(config('filesystems.default'))->exists($this->hero_path);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function creatorTags(): HasMany
    {
        return $this->hasMany(CreatorTag::class)
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function userPicks(): HasMany
    {
        return $this->hasMany(UserPick::class);
    }

    public function creatorOwners(): HasMany
    {
        return $this->hasMany(CreatorOwner::class);
    }

    public function creatorFavorites(): HasMany
    {
        return $this->hasMany(CreatorFavorite::class);
    }

    public function adminAuditLogs()
    {
        return $this->morphMany(SuperAdminAuditLog::class, 'auditable');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'creator_favorites')
            ->wherePivotNull('released_at')
            ->withTimestamps();
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->favoritedBy();
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'creator_owners')
            ->withPivot('role')
            ->withTimestamps();
    }
}
