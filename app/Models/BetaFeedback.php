<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'read_at',
        'read_by_user_id',
        'spam_at',
        'spam_by_user_id',
        'spam_reason',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'resolved_at' => 'datetime',
            'read_at' => 'datetime',
            'spam_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function readBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by_user_id');
    }

    public function spamBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spam_by_user_id');
    }

    public function isSpam(): bool
    {
        return $this->spam_at !== null;
    }

    public function scopeNotSpam(Builder $query): Builder
    {
        return $query->whereNull('spam_at');
    }

    public function scopeSpam(Builder $query): Builder
    {
        return $query->whereNotNull('spam_at');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->notSpam()->whereNull('read_at');
    }
}
