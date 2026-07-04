<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('plan_slug')->nullable()->default('free')->after('membership_tier')->index();
        });

        DB::table('users')
            ->whereIn('membership_tier', ['free', 'plus', 'pro'])
            ->update(['plan_slug' => DB::raw('membership_tier')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('plan_slug');
        });
    }
};
