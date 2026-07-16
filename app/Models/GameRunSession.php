<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRunSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(GameDay::class, 'game_day_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
