<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    protected $fillable = ['creator_channel_id', 'name', 'normalized_name', 'slug', 'subject_type', 'country_code', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creatorChannel(): BelongsTo
    {
        return $this->belongsTo(CreatorChannel::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(CreatorVideo::class, 'creator_video_subject')->withPivot(['relationship_type', 'is_primary'])->withTimestamps();
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }
}
