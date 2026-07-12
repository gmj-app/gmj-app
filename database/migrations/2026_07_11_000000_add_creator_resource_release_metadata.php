<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_favorites', function (Blueprint $table): void {
            $table->timestamp('released_at')->nullable()->after('user_id');
            $table->string('release_reason')->nullable()->after('released_at');
            $table->index(['user_id', 'released_at']);
        });

        Schema::table('recommendations', function (Blueprint $table): void {
            $table->timestamp('resource_released_at')->nullable()->after('status');
            $table->string('resource_release_reason')->nullable()->after('resource_released_at');
            $table->index(['submitted_by', 'resource_released_at']);
        });

        Schema::table('user_picks', function (Blueprint $table): void {
            $table->timestamp('released_at')->nullable()->after('vote_count');
            $table->string('release_reason')->nullable()->after('released_at');
            $table->index(['user_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::table('creator_favorites', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'released_at']);
            $table->dropColumn(['released_at', 'release_reason']);
        });
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->dropIndex(['submitted_by', 'resource_released_at']);
            $table->dropColumn(['resource_released_at', 'resource_release_reason']);
        });
        Schema::table('user_picks', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'released_at']);
            $table->dropColumn(['released_at', 'release_reason']);
        });
    }
};
