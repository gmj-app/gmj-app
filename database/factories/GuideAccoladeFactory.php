<?php

namespace Database\Factories;

use App\Models\GuideAccolade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuideAccolade>
 */
class GuideAccoladeFactory extends Factory
{
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'code' => str($label)->slug('_')->toString(),
            'label' => str($label)->title()->toString(),
            'description' => fake()->sentence(),
            'tier' => null,
            'ring_color' => 'blue',
            'ring_class' => 'ring-2 ring-blue-400',
            'badge_class' => 'bg-blue-500/15 text-blue-200 border-blue-400/40',
            'tooltip_template' => null,
            'priority' => fake()->numberBetween(1, 80),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
