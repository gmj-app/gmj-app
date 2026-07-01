<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('recommendations', 'submission_source')) {
            return;
        }

        Schema::table('recommendations', function (Blueprint $table): void {
            $table->string('submission_source')
                ->default('fan')
                ->after('submitted_by')
                ->index();
        });
    }

    public function down(): void
    {
        // The original submission-source migration owns removal of this column.
    }
};
