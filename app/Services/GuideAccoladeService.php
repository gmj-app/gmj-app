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
        return [
            self::FOUNDING_CODE => [
                'label' => 'Founding Guide',
                'description' => 'First 100 guides to join Guide My Journey.',
                'tier' => 'founding',
                'ring_color' => 'gold',
                'ring_class' => 'ring-2 ring-yellow-400',
                'badge_class' => 'bg-yellow-500/15 text-yellow-200 border-yellow-400/40',
                'tooltip_template' => 'Founding Guide (#{guide_number})',
                'priority' => 100,
                'is_active' => true,
            ],
            self::OG_CODE => [
                'label' => 'OG Guide',
                'description' => 'First 500 guides to join Guide My Journey.',
                'tier' => 'og',
                'ring_color' => 'silver',
                'ring_class' => 'ring-2 ring-slate-300',
                'badge_class' => 'bg-slate-400/15 text-slate-100 border-slate-300/40',
                'tooltip_template' => 'OG Guide (#{guide_number})',
                'priority' => 90,
                'is_active' => true,
            ],
        ];
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
        if ($user->guide_number === null) {
            return;
        }

        $this->ensureInitialAccolades();

        $earlyAccolades = GuideAccolade::query()
            ->whereIn('code', [self::FOUNDING_CODE, self::OG_CODE])
            ->get()
            ->keyBy('code');

        $targetCode = match (true) {
            $user->guide_number >= 1 && $user->guide_number <= 100 => self::FOUNDING_CODE,
            $user->guide_number >= 101 && $user->guide_number <= 500 => self::OG_CODE,
            default => null,
        };

        foreach ([self::FOUNDING_CODE, self::OG_CODE] as $code) {
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
