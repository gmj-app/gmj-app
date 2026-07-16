<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameDailyBest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['distance' => 'float', 'accepted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(GameRun::class, 'game_run_id');
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(GameDay::class, 'game_day_id');
    }
}
