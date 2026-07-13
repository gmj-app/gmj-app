<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'internal_name' => fake()->sentence(3),
            'title' => fake()->sentence(5),
            'message' => fake()->paragraph(),
            'audience' => Announcement::AUDIENCE_ALL,
            'action_url' => '/notifications',
            'action_label' => 'View update',
            'icon' => 'megaphone',
            'severity' => 'info',
            'status' => Announcement::STATUS_DRAFT,
            'created_by_user_id' => User::factory(),
            'updated_by_user_id' => User::factory(),
        ];
    }
}
