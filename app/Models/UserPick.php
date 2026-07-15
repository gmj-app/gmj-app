<?php

namespace App\Models;

use Database\Factories\UserPickFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPick extends Model
{
    /** @use HasFactory<UserPickFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'creator_id',
        'recommendation_id',
        'vote_count',
        'rank',
        'released_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'vote_count' => 'integer',
            'released_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class);
    }

    /** @param Builder<UserPick> $query */
    public function scopeActiveSupport(Builder $query): Builder
    {
        return $query->whereNull('released_at')->where('vote_count', '>', 0);
    }

    /**
     * Released capacity is still valid history unless moderation explicitly
     * invalidated the allocation.
     *
     * @param  Builder<UserPick>  $query
     */
    public function scopeValidHistoricalSupport(Builder $query): Builder
    {
        return $query->where('vote_count', '>', 0)
            ->where(fn (Builder $query) => $query
                ->whereNull('release_reason')
                ->orWhere('release_reason', '!=', 'request_removed'));
    }
}
