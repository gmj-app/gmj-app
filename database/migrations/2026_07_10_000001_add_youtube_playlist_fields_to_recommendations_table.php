<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->string('media_type')->nullable()->after('recommendation_type')->index();
            $table->string('youtube_playlist_id')->nullable()->after('youtube_video_id')->index();
            $table->text('thumbnail_url')->nullable()->after('channel_title');
            $table->string('source_title')->nullable()->after('thumbnail_url');
            $table->string('source_channel')->nullable()->after('source_title');
            $table->unsignedInteger('source_item_count')->nullable()->after('source_channel');
            $table->json('source_metadata')->nullable()->after('source_item_count');
            $table->string('published_media_type')->nullable()->after('published_normalized_url');
            $table->string('published_playlist_id')->nullable()->after('published_video_id')->index();
            $table->unsignedInteger('published_item_count')->nullable()->after('published_playlist_id');
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->dropIndex(['media_type']);
            $table->dropIndex(['youtube_playlist_id']);
            $table->dropIndex(['published_playlist_id']);
            $table->dropColumn([
                'media_type',
                'youtube_playlist_id',
                'thumbnail_url',
                'source_title',
                'source_channel',
                'source_item_count',
                'source_metadata',
                'published_media_type',
                'published_playlist_id',
                'published_item_count',
            ]);
        });
    }
};
