<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorGuideOverride extends Model
{
    protected $fillable = ['creator_id', 'user_id', 'request_limit', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['request_limit' => 'integer'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
