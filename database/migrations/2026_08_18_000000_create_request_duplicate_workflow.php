<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->foreignId('merged_into_request_id')->nullable()->after('status')->constrained('recommendations')->nullOnDelete();
            $table->timestamp('merged_at')->nullable()->after('merged_into_request_id');
            $table->foreignId('merged_by_user_id')->nullable()->after('merged_at')->constrained('users')->nullOnDelete();
            $table->index(['creator_id', 'status', 'merged_into_request_id'], 'recommendations_duplicate_state_index');
        });
        Schema::create('request_duplicate_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_low_id')->constrained('recommendations')->restrictOnDelete();
            $table->foreignId('request_high_id')->constrained('recommendations')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('resolution', 20)->nullable();
            $table->foreignId('survivor_request_id')->nullable()->constrained('recommendations')->restrictOnDelete();
            $table->foreignId('merged_request_id')->nullable()->constrained('recommendations')->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('merge_summary')->nullable();
            $table->timestamps();
            $table->unique(['request_low_id', 'request_high_id'], 'request_duplicate_cases_pair_unique');
            $table->index(['creator_id', 'status', 'created_at'], 'request_duplicate_cases_moderation_index');
        });
        Schema::create('request_duplicate_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('request_duplicate_cases')->cascadeOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('note', 300)->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'reported_by_user_id'], 'request_duplicate_reports_reporter_unique');
            $table->index(['reported_by_user_id', 'created_at'], 'request_duplicate_reports_rate_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_duplicate_reports');
        Schema::dropIfExists('request_duplicate_cases');
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->dropIndex('recommendations_duplicate_state_index');
            $table->dropConstrainedForeignId('merged_by_user_id');
            $table->dropConstrainedForeignId('merged_into_request_id');
            $table->dropColumn('merged_at');
        });
    }
};
