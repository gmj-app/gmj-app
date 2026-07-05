<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('can_access_video_tools')->default(false)->after('plan_slug');
        });

        Schema::create('youtube_channel_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('google_account_id')->nullable();
            $table->string('channel_id')->nullable();
            $table->string('channel_title')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('youtube_description_backups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('video_id');
            $table->string('video_title');
            $table->longText('original_description');
            $table->longText('new_description')->nullable();
            $table->string('operation_batch_id')->nullable()->index();
            $table->timestamps();

            $table->index('video_id');
        });

        Schema::create('video_tool_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('operation_batch_id')->nullable()->index();
            $table->string('video_id')->nullable()->index();
            $table->string('video_title')->nullable();
            $table->string('action');
            $table->string('status');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_tool_audit_logs');
        Schema::dropIfExists('youtube_description_backups');
        Schema::dropIfExists('youtube_channel_tokens');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('can_access_video_tools');
        });
    }
};
