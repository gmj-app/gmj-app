<?php

namespace Database\Factories;

use App\Models\CreatorChannel;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Subject> */ class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return ['creator_channel_id' => CreatorChannel::factory(), 'name' => $name, 'normalized_name' => Str::lower($name), 'slug' => Str::slug($name), 'is_active' => true];
    }
}
