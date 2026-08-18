<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestDuplicateCase extends Model
{
    protected $fillable = ['creator_id', 'request_low_id', 'request_high_id', 'status', 'resolution', 'survivor_request_id', 'merged_request_id', 'reviewed_by_user_id', 'reviewed_at', 'merge_summary'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'merge_summary' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function requestLow(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class, 'request_low_id');
    }

    public function requestHigh(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class, 'request_high_id');
    }

    public function survivor(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class, 'survivor_request_id');
    }

    public function mergedRequest(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class, 'merged_request_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(RequestDuplicateReport::class, 'case_id');
    }
}
