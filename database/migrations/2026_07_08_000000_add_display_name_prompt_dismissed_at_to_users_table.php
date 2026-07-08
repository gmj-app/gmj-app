<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'display_name_prompt_dismissed_at')) {
                $table->timestamp('display_name_prompt_dismissed_at')->nullable()->after('public_profile_completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'display_name_prompt_dismissed_at')) {
                $table->dropColumn('display_name_prompt_dismissed_at');
            }
        });
    }
};
