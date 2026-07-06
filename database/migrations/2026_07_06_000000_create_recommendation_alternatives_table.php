<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_alternatives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alternative_url', 2048);
            $table->string('alternative_video_id')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['recommendation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_alternatives');
    }
};
