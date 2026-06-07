<?php

namespace Database\Seeders;

use App\Models\Creator;
use Illuminate\Database\Seeder;

class CreatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Creator::query()->updateOrCreate(
            ['slug' => 'jfragment'],
            [
                'display_name' => 'JFragment',
                'channel_url' => 'https://www.youtube.com/@jasoncalebjohnson',
            ],
        );
    }
}
