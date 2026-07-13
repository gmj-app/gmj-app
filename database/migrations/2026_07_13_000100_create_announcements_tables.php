<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('internal_name');
            $table->string('title', 150);
            $table->text('message');
            $table->string('audience', 32);
            $table->text('action_url')->nullable();
            $table->string('action_label', 80)->nullable();
            $table->string('icon', 50)->default('megaphone');
            $table->string('severity', 20)->default('info');
            $table->string('status', 20)->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('recipient_count')->default(0);
            $table->unsignedBigInteger('delivered_count')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'starts_at']);
            $table->index(['audience', 'status']);
        });

        Schema::create('announcement_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['announcement_id', 'user_id']);
            $table->index(['announcement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_deliveries');
        Schema::dropIfExists('announcements');
    }
};
