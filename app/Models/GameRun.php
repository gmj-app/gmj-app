<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRun extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['distance' => 'float', 'event_digest' => 'array', 'validation_flags' => 'array', 'submitted_at' => 'datetime', 'accepted_at' => 'datetime', 'invalidated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(GameDay::class, 'game_day_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameRunSession::class, 'game_run_session_id');
    }
}
