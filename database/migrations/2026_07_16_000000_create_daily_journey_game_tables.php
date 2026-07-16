<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_days', function (Blueprint $table): void {
            $table->id();
            $table->string('game_key', 64);
            $table->date('local_date');
            $table->string('timezone', 64);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 20)->default('open');
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('winner_run_id')->nullable();
            $table->unsignedBigInteger('winner_score')->nullable();
            $table->timestamps();
            $table->unique(['game_key', 'local_date']);
            $table->index(['status', 'ends_at']);
        });
        Schema::create('game_run_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_token')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_day_id')->constrained()->cascadeOnDelete();
            $table->string('game_key', 64);
            $table->string('status', 20)->default('issued');
            $table->unsignedBigInteger('random_seed');
            $table->string('game_version', 64);
            $table->timestamp('issued_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['game_day_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
        Schema::create('game_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_run_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('score');
            $table->decimal('distance', 12, 2);
            $table->unsignedInteger('duration_ms');
            $table->unsignedInteger('collectible_count')->default(0);
            $table->unsignedSmallInteger('powerup_pickup_count')->default(0);
            $table->unsignedSmallInteger('powerup_use_count')->default(0);
            $table->unsignedSmallInteger('maximum_speed_tier')->default(1);
            $table->json('event_digest')->nullable();
            $table->string('validation_status', 20);
            $table->json('validation_flags')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('client_version', 64);
            $table->timestamp('submitted_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->foreignId('invalidated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('invalidation_reason')->nullable();
            $table->timestamps();
            $table->index(['game_day_id', 'validation_status', 'invalidated_at']);
            $table->index(['user_id', 'game_day_id']);
            $table->index(['game_day_id', 'score', 'accepted_at']);
        });
        Schema::table('game_days', fn (Blueprint $table) => $table->foreign('winner_run_id')->references('id')->on('game_runs')->nullOnDelete());
        Schema::create('game_daily_bests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('score');
            $table->decimal('distance', 12, 2);
            $table->unsignedInteger('duration_ms');
            $table->timestamp('accepted_at');
            $table->timestamps();
            $table->unique(['game_day_id', 'user_id']);
            $table->index(['game_day_id', 'score', 'distance', 'accepted_at']);
        });
        Schema::create('game_daily_champions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_day_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_run_id')->constrained()->cascadeOnDelete();
            $table->date('local_date');
            $table->unsignedBigInteger('score');
            $table->decimal('distance', 12, 2);
            $table->timestamp('finalized_at');
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamps();
            $table->index(['local_date', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_daily_champions');
        Schema::dropIfExists('game_daily_bests');
        Schema::table('game_days', fn (Blueprint $table) => $table->dropForeign(['winner_run_id']));
        Schema::dropIfExists('game_runs');
        Schema::dropIfExists('game_run_sessions');
        Schema::dropIfExists('game_days');
    }
};
