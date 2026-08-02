<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('creator_profiles')) {
            Schema::create('creator_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('display_name');
                $table->string('slug')->unique();
                $table->string('timezone')->default('America/New_York');
                $table->string('default_currency', 3)->default('USD');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('creator_channels')) {
            Schema::create('creator_channels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('creator_profile_id')->constrained()->cascadeOnDelete();
                $table->string('platform')->index();
                $table->string('platform_channel_id')->nullable();
                $table->string('handle')->nullable();
                $table->string('channel_name');
                $table->string('subject_label')->default('Subject');
                $table->string('content_item_label')->default('Content Item');
                $table->string('category_label')->default('Category');
                $table->string('default_publish_timezone')->default('America/New_York');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['platform', 'platform_channel_id']);
            });
        }

        if (! Schema::hasTable('creator_videos')) {
            Schema::create('creator_videos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('creator_channel_id')->constrained()->cascadeOnDelete();
                $table->string('platform_video_id')->index();
                $table->string('title');
                $table->longText('description')->nullable();
                $table->string('video_url', 2048)->nullable();
                $table->string('thumbnail_url', 2048)->nullable();
                $table->timestampTz('published_at')->nullable()->index();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('video_format')->default('unknown')->index();
                $table->string('content_type')->default('other')->index();
                $table->boolean('is_premiere')->default(false);
                $table->boolean('is_live')->default(false);
                $table->boolean('is_short')->default(false);
                $table->boolean('is_documentary')->default(false);
                $table->boolean('is_interview')->default(false);
                $table->boolean('is_monetized')->nullable();
                $table->string('copyright_status')->default('unknown');
                $table->timestamps();
                $table->unique(['creator_channel_id', 'platform_video_id']);
            });
        }

        if (! Schema::hasTable('video_performance_snapshots')) {
            Schema::create('video_performance_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('creator_video_id')->constrained()->cascadeOnDelete();
                $table->date('snapshot_date')->index();
                $table->string('source')->index();
                $table->unsignedBigInteger('views')->nullable();
                $table->unsignedBigInteger('impressions')->nullable();
                $table->decimal('impressions_ctr', 7, 4)->nullable();
                $table->decimal('watch_time_minutes', 16, 4)->nullable();
                $table->unsignedInteger('average_view_duration_seconds')->nullable();
                $table->decimal('average_percentage_viewed', 7, 4)->nullable();
                $table->unsignedBigInteger('likes')->nullable();
                $table->unsignedBigInteger('comments')->nullable();
                $table->unsignedBigInteger('shares')->nullable();
                $table->integer('subscribers_gained')->nullable();
                $table->integer('subscribers_lost')->nullable();
                $table->decimal('estimated_revenue', 16, 4)->nullable();
                $table->decimal('rpm', 16, 4)->nullable();
                $table->decimal('cpm', 16, 4)->nullable();
                $table->bigInteger('hype_points')->nullable();
                $table->unsignedBigInteger('views_first_24_hours')->nullable();
                $table->unsignedBigInteger('views_first_7_days')->nullable();
                $table->unsignedBigInteger('views_first_28_days')->nullable();
                $table->timestamps();
                $table->unique(['creator_video_id', 'snapshot_date', 'source'], 'creator_video_snapshot_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('video_performance_snapshots');
        Schema::dropIfExists('creator_videos');
        Schema::dropIfExists('creator_channels');
        Schema::dropIfExists('creator_profiles');
    }
};
