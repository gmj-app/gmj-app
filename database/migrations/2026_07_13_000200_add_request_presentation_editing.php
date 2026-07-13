<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->string('display_title_override', 160)->nullable()->after('source_title');
            $table->text('request_context')->nullable()->after('reason');
        });

        Schema::create('request_presentation_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users');
            $table->string('actor_context', 32);
            $table->string('action', 40)->default('updated');
            $table->string('previous_display_title_override', 160)->nullable();
            $table->string('new_display_title_override', 160)->nullable();
            $table->text('previous_request_context')->nullable();
            $table->text('new_request_context')->nullable();
            $table->json('changed_fields');
            $table->timestamps();
            $table->index(['recommendation_id', 'created_at'], 'request_presentation_revision_history');
        });

        Schema::create('request_identity_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->text('proposed_url')->nullable();
            $table->string('proposed_topic', 255)->nullable();
            $table->text('explanation');
            $table->string('status', 24)->default('pending');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['recommendation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_identity_corrections');
        Schema::dropIfExists('request_presentation_revisions');
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->dropColumn(['display_title_override', 'request_context']);
        });
    }
};
