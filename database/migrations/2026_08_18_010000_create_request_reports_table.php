<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->restrictOnDelete();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 50);
            $table->string('details', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('resolution', 20)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['recommendation_id', 'reported_by_user_id']);
            $table->index(['creator_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_reports');
    }
};
