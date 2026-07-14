<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->timestamp('voting_closed_at')->nullable()->after('status');
            $table->unsignedInteger('vote_total_at_close')->nullable()->after('voting_closed_at');
            $table->unsignedInteger('supporter_count_at_close')->nullable()->after('vote_total_at_close');
            $table->index(['creator_id', 'status', 'vote_total_at_close'], 'recommendations_creator_status_closed_votes');
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->dropIndex('recommendations_creator_status_closed_votes');
            $table->dropColumn(['voting_closed_at', 'vote_total_at_close', 'supporter_count_at_close']);
        });
    }
};
