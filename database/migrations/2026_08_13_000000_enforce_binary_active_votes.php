<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_picks', function (Blueprint $table): void {
            $table->index(['recommendation_id', 'released_at'], 'user_picks_request_active_index');
        });

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE user_picks ADD CONSTRAINT user_picks_active_vote_binary CHECK (released_at IS NOT NULL OR vote_count = 1)'),
            'pgsql' => DB::statement('ALTER TABLE user_picks ADD CONSTRAINT user_picks_active_vote_binary CHECK (released_at IS NOT NULL OR vote_count = 1)'),
            'sqlite' => DB::unprepared('CREATE TRIGGER user_picks_active_vote_binary_insert BEFORE INSERT ON user_picks WHEN NEW.released_at IS NULL AND NEW.vote_count <> 1 BEGIN SELECT RAISE(ABORT, \'active vote_count must equal 1\'); END; CREATE TRIGGER user_picks_active_vote_binary_update BEFORE UPDATE ON user_picks WHEN NEW.released_at IS NULL AND NEW.vote_count <> 1 BEGIN SELECT RAISE(ABORT, \'active vote_count must equal 1\'); END;'),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE user_picks DROP CHECK user_picks_active_vote_binary'),
            'pgsql' => DB::statement('ALTER TABLE user_picks DROP CONSTRAINT user_picks_active_vote_binary'),
            'sqlite' => DB::unprepared('DROP TRIGGER IF EXISTS user_picks_active_vote_binary_insert; DROP TRIGGER IF EXISTS user_picks_active_vote_binary_update;'),
            default => null,
        };

        Schema::table('user_picks', fn (Blueprint $table) => $table->dropIndex('user_picks_request_active_index'));
    }
};
