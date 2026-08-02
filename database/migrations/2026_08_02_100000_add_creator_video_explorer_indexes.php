<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_videos', function (Blueprint $table) {
            $table->index('copyright_status', 'creator_videos_copyright_status_index');
            $table->index('is_monetized', 'creator_videos_is_monetized_index');
        });
    }

    public function down(): void
    {
        Schema::table('creator_videos', function (Blueprint $table) {
            $table->dropIndex('creator_videos_copyright_status_index');
            $table->dropIndex('creator_videos_is_monetized_index');
        });
    }
};
