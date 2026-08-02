<?php

namespace App\Models;

use Database\Factories\CreatorChannelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorChannel extends Model
{
    /** @use HasFactory<CreatorChannelFactory> */
    use HasFactory;

    protected $fillable = ['creator_profile_id', 'platform', 'platform_channel_id', 'handle', 'channel_name', 'subject_label', 'content_item_label', 'category_label', 'default_publish_timezone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class, 'creator_profile_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CreatorVideo::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
