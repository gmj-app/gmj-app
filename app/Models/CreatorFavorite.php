<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorFavorite extends Model
{
    protected $fillable = [
        'creator_id',
        'user_id',
        'released_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
