<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PlanEntitlementService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'email',
        'google_id',
        'avatar_url',
        'auth_provider',
        'membership_tier',
        'plan_slug',
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
            'password' => 'hashed',
        ];
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

        return in_array(strtolower((string) $this->email), config('gmj.admin_emails', []), true);
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
        return $this->userPicks()
            ->where('creator_id', $creator->id)
            ->whereHas('recommendation', fn ($query) => $query
                ->whereIn('status', Recommendation::upvoteConsumingStatuses()))
            ->count();
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
}
