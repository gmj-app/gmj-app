<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_identity_corrections', function (Blueprint $table): void {
            $table->foreignId('replacement_recommendation_id')->nullable()->constrained('recommendations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_identity_corrections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('replacement_recommendation_id');
        });
    }
};
