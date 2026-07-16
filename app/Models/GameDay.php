<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameDay extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['local_date' => 'date', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'finalized_at' => 'datetime'];
    }

    public function dailyBests(): HasMany
    {
        return $this->hasMany(GameDailyBest::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(GameRun::class);
    }
}
