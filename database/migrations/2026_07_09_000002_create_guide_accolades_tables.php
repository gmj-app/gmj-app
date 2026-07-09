<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_accolades', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('tier')->nullable();
            $table->string('ring_color')->nullable();
            $table->string('ring_class')->nullable();
            $table->string('badge_class')->nullable();
            $table->string('tooltip_template')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('guide_accolade_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guide_accolade_id')->constrained()->cascadeOnDelete();
            $table->string('source')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'guide_accolade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_accolade_user');
        Schema::dropIfExists('guide_accolades');
    }
};
