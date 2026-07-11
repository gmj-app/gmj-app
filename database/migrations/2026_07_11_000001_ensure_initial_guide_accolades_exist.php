<?php

use App\Services\GuideAccoladeService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(GuideAccoladeService::class)->ensureInitialAccolades();
    }

    public function down(): void
    {
        // These baseline tiers are application data and must survive rollbacks.
    }
};
