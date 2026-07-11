<?php

namespace App\Models;

use App\Services\GuideAccoladeResolver;
use Database\Factories\GuideAccoladeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GuideAccolade extends Model
{
    public const RULE_GUIDE_NUMBER_RANGE = 'guide_number_range';

    /** @use HasFactory<GuideAccoladeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'short_label',
        'description',
        'rule_type',
        'minimum_guide_number',
        'maximum_guide_number',
        'display_number_plate',
        'plate_prefix',
        'css_class',
        'icon',
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
            'display_number_plate' => 'boolean',
            'minimum_guide_number' => 'integer',
            'maximum_guide_number' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $forgetCache = function (): void {
            app(GuideAccoladeResolver::class)->forgetCache();
        };

        static::saved($forgetCache);
        static::deleted($forgetCache);
    }

    /**
     * @return array{key: string, name: string, short_label: string, description: ?string, css_class: string, icon: ?string, display_number_plate: bool, plate_text: ?string, tooltip: string, priority: int}
     */
    public function viewDataFor(User $user): array
    {
        $plateText = $this->display_number_plate && $user->guide_number !== null
            ? ($this->plate_prefix ?? '#').$user->guide_number
            : null;

        return [
            'key' => $this->code,
            'name' => $this->label,
            'short_label' => $this->short_label ?: $this->label,
            'description' => $this->description,
            'css_class' => $this->css_class ?: 'accolade-default',
            'icon' => $this->icon,
            'display_number_plate' => (bool) $this->display_number_plate,
            'plate_text' => $plateText,
            'tooltip' => $plateText ? "{$this->label} ({$plateText})" : $this->label,
            'priority' => (int) $this->priority,
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
