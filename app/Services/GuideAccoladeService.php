<?php

namespace App\Services;

use App\Models\GuideAccolade;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GuideAccoladeService
{
    public const FOUNDING_CODE = 'founding_guide';

    public const OG_CODE = 'og_guide';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function initialAccolades(): array
    {
        return collect(config('guide_accolades.early_guide_tiers', []))
            ->mapWithKeys(fn (array $tier): array => [
                $tier['key'] => [
                    'label' => $tier['label'],
                    'description' => $tier['description'],
                    'tier' => str($tier['key'])->beforeLast('_guide')->toString(),
                    'ring_color' => $tier['variant'],
                    'ring_class' => $tier['variant'] === 'gold'
                        ? 'ring-[3px] ring-yellow-400'
                        : 'ring-[3px] ring-slate-300',
                    'badge_class' => $tier['variant'] === 'gold'
                        ? 'border-yellow-400/70 bg-slate-950/95 text-yellow-300'
                        : 'border-slate-200/80 bg-gradient-to-br from-slate-600 via-slate-800 to-slate-950 text-slate-100',
                    'tooltip_template' => $tier['label'].' (#{guide_number})',
                    'priority' => $tier['priority'],
                    'is_active' => true,
                ],
            ])
            ->all();
    }

    /**
     * @return array{key: string, label: string, guide_number: int, plate_text: string, css_variant: string}|null
     */
    public function resolveEarlyGuideAccolade(?int $guideNumber): ?array
    {
        if ($guideNumber === null) {
            return null;
        }

        $tier = collect(config('guide_accolades.early_guide_tiers', []))
            ->first(fn (array $candidate): bool => $guideNumber >= $candidate['min'] && $guideNumber <= $candidate['max']);

        return $tier ? [
            'key' => $tier['key'],
            'label' => $tier['label'],
            'guide_number' => $guideNumber,
            'plate_text' => '#'.$guideNumber,
            'css_variant' => $tier['variant'],
        ] : null;
    }

    public function ensureInitialAccolades(): void
    {
        foreach ($this->initialAccolades() as $code => $attributes) {
            GuideAccolade::query()->updateOrCreate(['code' => $code], $attributes);
        }
    }

    public function awardEarlyGuideAccolades(User $user): void
    {
        $this->syncEarlyGuideAccolades($user);
    }

    public function syncEarlyGuideAccolades(User $user): void
    {
        $this->ensureInitialAccolades();

        $earlyAccoladeCodes = collect(config('guide_accolades.early_guide_tiers', []))
            ->pluck('key')
            ->all();

        $earlyAccolades = GuideAccolade::query()
            ->whereIn('code', $earlyAccoladeCodes)
            ->get()
            ->keyBy('code');

        $targetCode = $this->resolveEarlyGuideAccolade($user->guide_number)['key'] ?? null;

        foreach ($earlyAccoladeCodes as $code) {
            $accolade = $earlyAccolades->get($code);

            if (! $accolade) {
                continue;
            }

            if ($code === $targetCode) {
                $user->guideAccolades()->syncWithoutDetaching([
                    $accolade->id => [
                        'source' => 'automatic_guide_number',
                        'awarded_at' => now(),
                    ],
                ]);

                continue;
            }

            $user->guideAccolades()->detach($accolade->id);
        }
    }

    /**
     * @return Collection<int, GuideAccolade>
     */
    public function getDisplayAccolades(User $user): Collection
    {
        return $user->activeGuideAccolades()
            ->orderByDesc('priority')
            ->orderByDesc('guide_accolade_user.awarded_at')
            ->orderBy('guide_accolades.id')
            ->get();
    }

    public function getPrimaryDisplayAccolade(User $user): ?GuideAccolade
    {
        return $this->getDisplayAccolades($user)->first();
    }
}
