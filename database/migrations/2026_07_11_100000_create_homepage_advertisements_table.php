<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_advertisements', function (Blueprint $table): void {
            $table->id();
            $table->string('internal_name');
            $table->string('advertiser_name')->nullable();
            $table->string('image_path');
            $table->text('destination_url');
            $table->string('alt_text');
            $table->string('cta_label')->nullable();
            $table->unsignedInteger('placement');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('click_count')->default(0);
            $table->unsignedBigInteger('impression_count')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index('placement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_advertisements');
    }
};
