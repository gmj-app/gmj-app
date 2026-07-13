<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->string('deduplication_key', 191)->nullable();
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_index');
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_recent_index');
            $table->unique(['notifiable_type', 'notifiable_id', 'deduplication_key'], 'notifications_deduplication_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
