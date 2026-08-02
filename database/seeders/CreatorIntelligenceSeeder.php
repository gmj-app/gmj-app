<?php

namespace Database\Seeders;

use App\Models\CreatorProfile;
use Illuminate\Database\Seeder;

class CreatorIntelligenceSeeder extends Seeder
{
    public function run(): void
    {
        $profile = CreatorProfile::query()->updateOrCreate(
            ['slug' => 'jfragment'],
            ['display_name' => 'JFragment', 'timezone' => 'America/New_York', 'default_currency' => 'USD'],
        );

        $profile->channels()->updateOrCreate(
            ['platform' => 'youtube', 'channel_name' => 'JFragment'],
            ['subject_label' => 'Artist', 'content_item_label' => 'Song', 'category_label' => 'Genre', 'default_publish_timezone' => 'America/New_York', 'is_active' => true],
        );
    }
}
