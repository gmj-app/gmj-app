<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->string('recommendation_type')->default('youtube')->after('submitted_by');
            $table->text('description')->nullable()->after('category');
            $table->string('channel_title')->nullable()->after('youtube_video_id');
            $table->string('youtube_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropColumn(['recommendation_type', 'description', 'channel_title']);
            $table->string('youtube_url')->nullable(false)->change();
        });
    }
};
