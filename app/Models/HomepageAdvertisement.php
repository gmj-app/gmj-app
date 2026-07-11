<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class HomepageAdvertisement extends Model
{
    use SoftDeletes;

    protected $fillable = ['internal_name', 'advertiser_name', 'image_path', 'destination_url', 'alt_text', 'cta_label', 'placement', 'is_active', 'starts_at', 'ends_at', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['placement' => 'integer', 'is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'click_count' => 'integer', 'impression_count' => 'integer'];
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active && ($this->starts_at === null || $this->starts_at->lte(now()))
            && ($this->ends_at === null || $this->ends_at->gte(now())) && $this->deleted_at === null;
    }

    public function displayLabel(): string
    {
        return 'Sponsored';
    }

    public function safeDestinationUrl(): string
    {
        return strtolower((string) parse_url($this->destination_url, PHP_URL_SCHEME)) === 'https' ? $this->destination_url : route('home');
    }

    public function imageUrl(): string
    {
        return Storage::disk(config('filesystems.default'))->url($this->image_path);
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Disabled';
        }
        if ($this->starts_at?->isFuture()) {
            return 'Scheduled';
        }
        if ($this->ends_at?->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }
}
