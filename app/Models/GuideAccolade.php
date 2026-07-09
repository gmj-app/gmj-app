<?php

namespace App\Models;

use Database\Factories\GuideAccoladeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GuideAccolade extends Model
{
    /** @use HasFactory<GuideAccoladeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'description',
        'tier',
        'ring_color',
        'ring_class',
        'badge_class',
        'tooltip_template',
        'priority',
        'starts_at',
        'ends_at',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['source', 'awarded_at', 'expires_at', 'metadata'])
            ->withTimestamps();
    }

    public function isCurrentlyActive(): bool
    {
        $now = now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gte($now));
    }

    public function tooltipLineFor(User $user): string
    {
        $template = filled($this->tooltip_template)
            ? (string) $this->tooltip_template
            : (string) $this->label;

        if ($user->guide_number === null && str_contains($template, '{guide_number}')) {
            return (string) $this->label;
        }

        return str_replace(
            ['{guide_number}', '{label}', '{display_name}'],
            [(string) $user->guide_number, (string) $this->label, $user->publicName()],
            $template,
        );
    }

    public function ariaLineFor(User $user): string
    {
        if ($user->guide_number !== null && str_contains((string) $this->tooltip_template, '#{guide_number}')) {
            return "{$this->label} number {$user->guide_number}";
        }

        return $this->tooltipLineFor($user);
    }
}
