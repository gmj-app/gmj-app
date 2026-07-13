<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beta_feedback', function (Blueprint $table): void {
            $table->timestamp('spam_at')->nullable()->after('read_by_user_id')->index();
            $table->foreignId('spam_by_user_id')->nullable()->after('spam_at')->constrained('users')->nullOnDelete();
            $table->string('spam_reason')->nullable()->after('spam_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('beta_feedback', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('spam_by_user_id');
            $table->dropIndex(['spam_at']);
            $table->dropColumn(['spam_at', 'spam_reason']);
        });
    }
};
