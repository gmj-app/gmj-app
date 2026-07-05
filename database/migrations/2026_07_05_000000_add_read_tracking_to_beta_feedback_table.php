<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beta_feedback', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('resolved_at');
            $table->foreignId('read_by_user_id')->nullable()->after('read_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('beta_feedback', function (Blueprint $table) {
            $table->dropConstrainedForeignId('read_by_user_id');
            $table->dropColumn('read_at');
        });
    }
};
