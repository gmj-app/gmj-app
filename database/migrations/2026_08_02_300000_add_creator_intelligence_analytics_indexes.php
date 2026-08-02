<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_performance_snapshots', function (Blueprint $table) {
            $table->index(['creator_video_id', 'source', 'snapshot_date'], 'snapshots_video_source_date_index');
        });
        Schema::table('creator_video_subject', function (Blueprint $table) {
            $table->index(['subject_id', 'is_primary', 'creator_video_id'], 'video_subject_report_index');
        });
        Schema::table('creator_video_content_item', function (Blueprint $table) {
            $table->index(['content_item_id', 'is_primary', 'creator_video_id'], 'video_content_item_report_index');
        });
        Schema::table('video_thumbnail_metadata', function (Blueprint $table) {
            $table->index(['background_style', 'reviewed_at'], 'thumbnail_background_review_index');
            $table->index(['layout_style', 'reviewed_at'], 'thumbnail_layout_review_index');
        });
        Schema::table('video_editorial_metadata', function (Blueprint $table) {
            $table->index(['creator_sentiment', 'reviewed_at'], 'editorial_sentiment_review_index');
            $table->index(['reaction_style', 'reviewed_at'], 'editorial_reaction_review_index');
        });
    }

    public function down(): void
    {
        Schema::table('video_editorial_metadata', fn (Blueprint $table) => $table->dropIndex('editorial_reaction_review_index'));
        Schema::table('video_editorial_metadata', fn (Blueprint $table) => $table->dropIndex('editorial_sentiment_review_index'));
        Schema::table('video_thumbnail_metadata', fn (Blueprint $table) => $table->dropIndex('thumbnail_layout_review_index'));
        Schema::table('video_thumbnail_metadata', fn (Blueprint $table) => $table->dropIndex('thumbnail_background_review_index'));
        Schema::table('creator_video_content_item', fn (Blueprint $table) => $table->dropIndex('video_content_item_report_index'));
        Schema::table('creator_video_subject', fn (Blueprint $table) => $table->dropIndex('video_subject_report_index'));
        Schema::table('video_performance_snapshots', fn (Blueprint $table) => $table->dropIndex('snapshots_video_source_date_index'));
    }
};
