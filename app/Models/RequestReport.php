<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestReport extends Model
{
    public const REASONS = ['spam' => 'Spam', 'harassment' => 'Harassment or abusive content', 'sexual' => 'Sexual or explicit content', 'hate' => 'Hate or discriminatory content', 'dangerous' => 'Dangerous or illegal content', 'misleading' => 'Misleading or unrelated', 'other' => 'Other'];

    protected $fillable = ['recommendation_id', 'creator_id', 'reported_by_user_id', 'reason', 'details', 'status', 'resolution', 'reviewed_by_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}
