<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameDailyChampion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['local_date' => 'date', 'distance' => 'float', 'finalized_at' => 'datetime', 'notification_sent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(GameDay::class, 'game_day_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(GameRun::class, 'game_run_id');
    }
}
