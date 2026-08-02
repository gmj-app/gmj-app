<?php

namespace App\Models;

use Database\Factories\ContentItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentItem extends Model
{
    /** @use HasFactory<ContentItemFactory> */
    use HasFactory;

    protected $fillable = ['creator_channel_id', 'subject_id', 'name', 'normalized_name', 'slug', 'aliases', 'content_item_type', 'release_date', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['aliases' => 'array', 'release_date' => 'date', 'is_active' => 'boolean'];
    }

    protected function name(): Attribute
    {
        return Attribute::set(fn (string $value) => trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    public function creatorChannel(): BelongsTo
    {
        return $this->belongsTo(CreatorChannel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(CreatorVideo::class, 'creator_video_content_item')->withPivot('is_primary')->withTimestamps();
    }
}
