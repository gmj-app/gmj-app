<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 60);
            $table->string('color')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['creator_id', 'slug']);
        });

        Schema::create('recommendation_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recommendation_id', 'creator_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_tag');
        Schema::dropIfExists('creator_tags');
    }
};
