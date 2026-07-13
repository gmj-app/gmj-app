<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_accolades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('accolade_key');
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            $table->string('track');
            $table->unsignedInteger('level');
            $table->unsignedBigInteger('progress_value_at_award')->nullable();
            $table->unsignedBigInteger('threshold_at_award')->nullable();
            $table->timestamp('awarded_at');
            $table->string('source_event_type')->nullable();
            $table->string('source_event_id')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedTinyInteger('featured_order')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'accolade_key'], 'user_accolades_subject_key_unique');
            $table->index(['user_id', 'subject_type', 'is_public']);
            $table->index(['track', 'accolade_key']);
        });

        Schema::create('accolade_progress', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            $table->string('track');
            $table->unsignedBigInteger('current_value')->default(0);
            $table->string('next_accolade_key')->nullable();
            $table->timestamp('evaluated_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'track'], 'accolade_progress_subject_track_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accolade_progress');
        Schema::dropIfExists('user_accolades');
    }
};
