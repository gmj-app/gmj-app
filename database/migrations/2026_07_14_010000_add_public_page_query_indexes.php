<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->index(['creator_id', 'status', 'deleted_at'], 'recommendations_creator_status_deleted');
            $table->index(['creator_id', 'status', 'created_at'], 'recommendations_creator_status_created');
            $table->index(['creator_id', 'status', 'published_at'], 'recommendations_creator_status_published');
            $table->index(['creator_id', 'status', 'resolved_at'], 'recommendations_creator_status_resolved');
        });

        Schema::table('user_picks', function (Blueprint $table): void {
            $table->index(['recommendation_id', 'released_at'], 'user_picks_request_released');
            $table->index(['user_id', 'creator_id', 'released_at'], 'user_picks_user_creator_released');
        });

        Schema::table('creator_favorites', function (Blueprint $table): void {
            $table->index(['creator_id', 'released_at'], 'creator_favorites_creator_released');
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->dropIndex('recommendations_creator_status_deleted');
            $table->dropIndex('recommendations_creator_status_created');
            $table->dropIndex('recommendations_creator_status_published');
            $table->dropIndex('recommendations_creator_status_resolved');
        });
        Schema::table('user_picks', function (Blueprint $table): void {
            $table->dropIndex('user_picks_request_released');
            $table->dropIndex('user_picks_user_creator_released');
        });
        Schema::table('creator_favorites', function (Blueprint $table): void {
            $table->dropIndex('creator_favorites_creator_released');
        });
    }
};
