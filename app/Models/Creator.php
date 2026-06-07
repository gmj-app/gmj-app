<?php

namespace App\Models;

use Database\Factories\CreatorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creator extends Model
{
    /** @use HasFactory<CreatorFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'display_name',
        'channel_url',
    ];

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function userPicks(): HasMany
    {
        return $this->hasMany(UserPick::class);
    }
}
