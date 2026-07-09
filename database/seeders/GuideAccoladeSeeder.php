<?php

namespace Database\Seeders;

use App\Services\GuideAccoladeService;
use Illuminate\Database\Seeder;

class GuideAccoladeSeeder extends Seeder
{
    public function run(GuideAccoladeService $guideAccolades): void
    {
        $guideAccolades->ensureInitialAccolades();
    }
}
