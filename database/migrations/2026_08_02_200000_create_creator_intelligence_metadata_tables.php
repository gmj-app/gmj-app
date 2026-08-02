<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_channel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->string('slug');
            $table->string('subject_type')->nullable()->index();
            $table->char('country_code', 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['creator_channel_id', 'normalized_name'], 'subjects_channel_normalized_unique');
            $table->unique(['creator_channel_id', 'slug'], 'subjects_channel_slug_unique');
        });
        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->string('slug');
            $table->string('content_item_type')->nullable()->index();
            $table->date('release_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['creator_channel_id', 'normalized_name'], 'content_items_channel_normalized_unique');
            $table->unique(['creator_channel_id', 'slug'], 'content_items_channel_slug_unique');
        });
        Schema::create('creator_video_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('relationship_type')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();
            $table->unique(['creator_video_id', 'subject_id'], 'video_subject_unique');
        });
        Schema::create('creator_video_content_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();
            $table->unique(['creator_video_id', 'content_item_id'], 'video_content_item_unique');
        });
        Schema::create('video_title_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_video_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('character_count')->default(0);
            $table->unsignedInteger('word_count')->default(0);
            foreach (['contains_question', 'contains_exclamation', 'contains_pipe', 'contains_parentheses', 'contains_all_caps', 'subject_name_present', 'content_item_name_present', 'negative_hook', 'curiosity_hook', 'emotional_hook', 'controversy_hook', 'technical_hook', 'discovery_hook'] as $field) {
                $table->boolean($field)->default(false);
            } $table->string('title_template')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('classified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('classified_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('video_thumbnail_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_video_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('thumbnail_version', 100)->nullable();
            $table->text('text_content')->nullable();
            $table->unsignedInteger('text_word_count')->nullable();
            $table->unsignedInteger('face_count')->nullable();
            foreach (['creator_face_visible', 'subject_face_visible', 'contains_question', 'contains_arrow', 'contains_circle', 'contains_flag', 'contains_logo'] as $field) {
                $table->boolean($field)->default(false);
            } $table->string('creator_expression')->nullable()->index();
            $table->string('background_style')->nullable();
            $table->string('dominant_color_label')->nullable();
            $table->string('layout_style')->nullable()->index();
            $table->string('text_position')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('classified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('classified_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('video_editorial_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_video_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('creator_sentiment')->nullable()->index();
            $table->string('reaction_style')->nullable()->index();
            foreach (['energy_level', 'technical_depth', 'humor_level', 'cultural_context_level'] as $field) {
                $table->string($field)->nullable();
            } $table->text('production_notes')->nullable();
            $table->foreignId('classified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('classified_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::table('creator_videos', function (Blueprint $table) {
            $table->unsignedTinyInteger('metadata_completion_percentage')->default(0)->index();
            $table->string('metadata_completion_status')->default('not_started')->index();
            $table->timestamp('metadata_completion_calculated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('creator_videos', function (Blueprint $table) {
            $table->dropIndex(['metadata_completion_percentage']);
            $table->dropIndex(['metadata_completion_status']);
            $table->dropColumn(['metadata_completion_percentage', 'metadata_completion_status', 'metadata_completion_calculated_at']);
        });
        Schema::dropIfExists('video_editorial_metadata');
        Schema::dropIfExists('video_thumbnail_metadata');
        Schema::dropIfExists('video_title_metadata');
        Schema::dropIfExists('creator_video_content_item');
        Schema::dropIfExists('creator_video_subject');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('subjects');
    }
};
