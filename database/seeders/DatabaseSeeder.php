<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(GuideAccoladeSeeder::class);
        $this->call(CreatorSeeder::class);

        if ($this->command?->getLaravel()->environment(['local', 'development', 'testing'])) {
            $this->call(CreatorIntelligenceSeeder::class);
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
