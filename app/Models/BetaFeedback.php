<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetaFeedback extends Model
{
    protected $table = 'beta_feedback';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'type',
        'message',
        'extra_context',
        'current_url',
        'user_agent',
        'platform',
        'timezone',
        'app_environment',
        'viewport_width',
        'viewport_height',
        'screen_width',
        'screen_height',
        'meta',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
