<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('youtube_url');
            $table->string('youtube_video_id')->nullable();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('category')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_pinned')->default(false);
            $table->string('published_reaction_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
