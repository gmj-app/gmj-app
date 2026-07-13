<?php

namespace App\Models;

use App\Services\Accolades\AccoladeDefinitionRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccolade extends Model
{
    protected $fillable = [
        'user_id', 'accolade_key', 'subject_type', 'subject_id', 'track', 'level',
        'progress_value_at_award', 'threshold_at_award', 'awarded_at',
        'source_event_type', 'source_event_id', 'metadata', 'is_featured',
        'featured_order', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
            'metadata' => 'array',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
            'progress_value_at_award' => 'integer',
            'threshold_at_award' => 'integer',
            'level' => 'integer',
            'featured_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, mixed>|null */
    public function definition(): ?array
    {
        return app(AccoladeDefinitionRepository::class)->find($this->accolade_key);
    }
}
