<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\GuideAccoladeService;
use App\Services\GuideNumberService;
use App\Services\PlanEntitlementService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'guide_number',
        'public_display_name',
        'public_handle',
        'public_profile_completed_at',
        'display_name_prompt_dismissed_at',
        'email',
        'google_id',
        'avatar_url',
        'auth_provider',
        'membership_tier',
        'plan_slug',
        'can_access_video_tools',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'guide_number' => 'integer',
            'public_profile_completed_at' => 'datetime',
            'display_name_prompt_dismissed_at' => 'datetime',
            'can_access_video_tools' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            app(GuideNumberService::class)->assignIfMissing($user);
            app(GuideAccoladeService::class)->awardEarlyGuideAccolades($user);
        });
    }

    public function publicName(): string
    {
        if (filled($this->public_display_name)) {
            return (string) $this->public_display_name;
        }

        if (filled($this->public_handle)) {
            return (string) $this->public_handle;
        }

        return 'Guide';
    }

    public function displayName(): string
    {
        return $this->publicName();
    }

    public function guideNumberLabel(): ?string
    {
        return $this->guide_number ? '#'.$this->guide_number : null;
    }

    public function isFoundingGuide(): bool
    {
        return $this->guide_number !== null
            && $this->guide_number >= 1
            && $this->guide_number <= 100;
    }

    public function foundingGuideNumberLabel(): ?string
    {
        return $this->isFoundingGuide() ? '#'.$this->guide_number : null;
    }

    public function guideAccoladeLabel(): ?string
    {
        return $this->isFoundingGuide() ? 'Founding Guide' : null;
    }

    public function guideAccoladeTooltipLine(): ?string
    {
        return $this->isFoundingGuide()
            ? "Founding Guide (#{$this->guide_number})"
            : null;
    }

    public function publicHandle(): ?string
    {
        return filled($this->public_handle) ? (string) $this->public_handle : null;
    }

    public function formattedPublicHandle(): ?string
    {
        return $this->publicHandle() ? '@'.$this->publicHandle() : null;
    }

    public function initialsForAvatar(): string
    {
        $source = filled($this->public_display_name)
            ? (string) $this->public_display_name
            : (filled($this->public_handle)
                ? (string) $this->public_handle
                : (string) str($this->email)->before('@'));

        $initials = collect(preg_split('/[\s._-]+/', trim($source)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => str($part)->substr(0, 1)->upper()->toString())
            ->implode('');

        return $initials !== '' ? $initials : 'G';
    }

    public function hasCompletedPublicProfile(): bool
    {
        return filled($this->public_display_name)
            && filled($this->public_handle)
            && $this->public_profile_completed_at !== null;
    }

    public function shouldSeeDisplayNamePrompt(): bool
    {
        if ($this->display_name_prompt_dismissed_at !== null) {
            return false;
        }

        $displayName = trim((string) $this->public_display_name);

        return $displayName === '' || strcasecmp($displayName, 'Guide') === 0;
    }

    public function recommendationsSubmitted(): HasMany
    {
        return $this->hasMany(Recommendation::class, 'submitted_by');
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

    public function favoriteCreators(): BelongsToMany
    {
        return $this->belongsToMany(Creator::class, 'creator_favorites')
            ->withTimestamps();
    }

    public function guideAccolades(): BelongsToMany
    {
        return $this->belongsToMany(GuideAccolade::class)
            ->withPivot(['source', 'awarded_at', 'expires_at', 'metadata'])
            ->withTimestamps();
    }

    public function activeGuideAccolades(): BelongsToMany
    {
        $now = now();

        return $this->guideAccolades()
            ->where('guide_accolades.is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('guide_accolades.starts_at')
                    ->orWhere('guide_accolades.starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('guide_accolades.ends_at')
                    ->orWhere('guide_accolades.ends_at', '>=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('guide_accolade_user.expires_at')
                    ->orWhere('guide_accolade_user.expires_at', '>=', $now);
            });
    }

    public function primaryGuideAccolade(): ?GuideAccolade
    {
        if ($this->relationLoaded('guideAccolades')) {
            $now = now();

            return $this->guideAccolades
                ->filter(function (GuideAccolade $accolade) use ($now): bool {
                    $expiresAt = $accolade->pivot?->expires_at;

                    return $accolade->isCurrentlyActive()
                        && ($expiresAt === null || $now->lte($expiresAt));
                })
                ->sort(function (GuideAccolade $first, GuideAccolade $second): int {
                    return ($second->priority <=> $first->priority)
                        ?: strcmp((string) ($second->pivot?->awarded_at ?? ''), (string) ($first->pivot?->awarded_at ?? ''))
                        ?: ($first->id <=> $second->id);
                })
                ->first();
        }

        return app(GuideAccoladeService::class)->getPrimaryDisplayAccolade($this);
    }

    /**
     * @return array<int, string>
     */
    public function guideAccoladeTooltipLines(): array
    {
        $primaryAccolade = $this->primaryGuideAccolade();

        return $primaryAccolade ? [$primaryAccolade->tooltipLineFor($this)] : [];
    }

    public function guideAvatarRingClass(): string
    {
        if ($this->isFoundingGuide()) {
            return 'ring-[3px] ring-yellow-400';
        }

        return (string) ($this->primaryGuideAccolade()?->ring_class ?? '');
    }

    public function guideAccoladeAriaLine(): ?string
    {
        return $this->primaryGuideAccolade()?->ariaLineFor($this);
    }

    public function youtubeChannelToken(): HasOne
    {
        return $this->hasOne(YoutubeChannelToken::class);
    }

    public function ownedCreators(): BelongsToMany
    {
        return $this->belongsToMany(Creator::class, 'creator_owners')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return array{label: string, reactors: int, suggestions_per_reactor: int, votes_per_reactor: int}
     */
    public function membershipLimits(): array
    {
        return app(PlanEntitlementService::class)->getLegacyLimitsForUser($this);
    }

    public function planSlug(): string
    {
        return app(PlanEntitlementService::class)->getUserPlan($this);
    }

    public function planName(): string
    {
        return app(PlanEntitlementService::class)->getUserPlanName($this);
    }

    public function canTestPaidPlans(): bool
    {
        if (! config('gmj.plan_testing_enabled')) {
            return false;
        }

        return $this->isAdminTester();
    }

    public function isAdminTester(): bool
    {
        return in_array(strtolower((string) $this->email), config('gmj.admin_emails', []), true)
            || (bool) $this->can_access_video_tools;
    }

    public function canViewBetaFeedbackInbox(): bool
    {
        return (bool) config('gmj.beta_feedback_enabled') && $this->isAdminTester();
    }

    public function reactorsUsed(): int
    {
        return $this->creatorFavoritesUsed();
    }

    public function reactorsRemaining(): int
    {
        return $this->creatorFavoritesRemaining();
    }

    public function creatorFavoriteLimit(): int
    {
        return $this->membershipLimits()['reactors'];
    }

    public function creatorFavoritesUsed(): int
    {
        return $this->creatorFavorites()->count();
    }

    public function creatorFavoritesRemaining(): int
    {
        return max(0, $this->creatorFavoriteLimit() - $this->creatorFavoritesUsed());
    }

    public function suggestionsUsedFor(Creator $creator): int
    {
        return $this->recommendationsSubmitted()
            ->where('creator_id', $creator->id)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereIn('status', Recommendation::suggestionConsumingStatuses())
            ->count();
    }

    public function suggestionsRemainingFor(Creator $creator): int
    {
        return max(
            0,
            $this->membershipLimits()['suggestions_per_reactor'] - $this->suggestionsUsedFor($creator),
        );
    }

    public function votesUsedFor(Creator $creator): int
    {
        return (int) $this->userPicks()
            ->whereHas('recommendation', fn ($query) => $query
                ->where('creator_id', $creator->id)
                ->votable())
            ->sum('vote_count');
    }

    public function votesRemainingFor(Creator $creator): int
    {
        return max(
            0,
            $this->membershipLimits()['votes_per_reactor'] - $this->votesUsedFor($creator),
        );
    }

    public function canSuggestTo(Creator $creator): bool
    {
        return ($this->hasFavoritedCreator($creator) || $this->canFavoriteMoreCreators())
            && $this->suggestionsRemainingFor($creator) > 0;
    }

    public function hasFavoritedCreator(Creator $creator): bool
    {
        return $this->creatorFavorites()
            ->where('creator_id', $creator->id)
            ->exists();
    }

    public function canFavoriteMoreCreators(): bool
    {
        return $this->creatorFavoritesRemaining() > 0;
    }

    public function votesAllocatedToRecommendation(Recommendation $recommendation): int
    {
        return (int) $this->userPicks()
            ->where('recommendation_id', $recommendation->id)
            ->sum('vote_count');
    }
}
