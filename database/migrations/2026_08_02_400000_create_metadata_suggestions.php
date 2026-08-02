<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_channels', function (Blueprint $table) {
            $table->json('metadata_parser_settings')->nullable();
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->json('aliases')->nullable();
        });
        Schema::table('content_items', function (Blueprint $table) {
            $table->json('aliases')->nullable();
        });
        Schema::table('creator_videos', function (Blueprint $table) {
            $table->boolean('content_item_not_applicable')->default(false)->index();
            $table->foreignId('content_item_not_applicable_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('content_item_not_applicable_at')->nullable();
        });
        Schema::create('metadata_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_video_id')->constrained()->cascadeOnDelete();
            $table->string('suggestion_type')->index();
            $table->foreignId('suggested_subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('suggested_content_item_id')->nullable()->constrained('content_items')->nullOnDelete();
            $table->string('suggested_display_value')->nullable();
            $table->string('confidence')->index();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->string('rule_name');
            $table->json('evidence')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('source_fingerprint', 64);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['creator_video_id', 'suggestion_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata_suggestions');
        Schema::table('creator_videos', fn (Blueprint $table) => $table->dropColumn(['content_item_not_applicable', 'content_item_not_applicable_by_user_id', 'content_item_not_applicable_at']));
        Schema::table('content_items', fn (Blueprint $table) => $table->dropColumn('aliases'));
        Schema::table('subjects', fn (Blueprint $table) => $table->dropColumn('aliases'));
        Schema::table('creator_channels', fn (Blueprint $table) => $table->dropColumn('metadata_parser_settings'));
    }
};
