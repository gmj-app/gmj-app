<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('data', 'like', '%game.daily_champion_awarded%')
                ->delete();
        }

        foreach (['accolade_progress', 'user_accolades'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'track')) {
                DB::table($table)->where('track', 'guide_daily_challenge_wins')->delete();
            }
        }

        if (Schema::hasTable('super_admin_audit_logs')) {
            DB::table('super_admin_audit_logs')->where('action', 'like', 'game.%')->delete();
        }

        Schema::dropIfExists('game_daily_champions');
        Schema::dropIfExists('game_daily_bests');

        if (Schema::hasTable('game_days') && Schema::hasColumn('game_days', 'winner_run_id')) {
            Schema::table('game_days', fn ($table) => $table->dropForeign(['winner_run_id']));
        }

        Schema::dropIfExists('game_runs');
        Schema::dropIfExists('game_run_sessions');
        Schema::dropIfExists('game_days');
    }

    public function down(): void
    {
        // Game data cannot be reconstructed safely after this targeted feature removal.
    }
};
