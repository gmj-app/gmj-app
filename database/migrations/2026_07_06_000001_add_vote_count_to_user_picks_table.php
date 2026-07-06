<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_picks', function (Blueprint $table): void {
            $table->unsignedInteger('vote_count')->default(1)->after('recommendation_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_picks', function (Blueprint $table): void {
            $table->dropColumn('vote_count');
        });
    }
};
