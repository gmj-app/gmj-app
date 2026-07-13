<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('request_identity_corrections', 'replacement_recommendation_id')) {
            return;
        }

        Schema::table('request_identity_corrections', function (Blueprint $table): void {
            $table->foreignId('replacement_recommendation_id')->nullable()->constrained('recommendations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // This compatibility migration may encounter a column created by an
        // earlier deployed version of the table-creation migration. It cannot
        // safely claim ownership of that column during rollback.
    }
};
