<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            if (! Schema::hasColumn('creators', 'bio')) {
                $table->text('bio')->nullable();
            }

            if (! Schema::hasColumn('creators', 'submission_instructions')) {
                $table->text('submission_instructions')->nullable();
            }

            if (! Schema::hasColumn('creators', 'submissions_open')) {
                $table->boolean('submissions_open')->default(true);
            }

            if (! Schema::hasColumn('creators', 'status')) {
                $table->string('status')->default('active');
            }

            if (! Schema::hasColumn('creators', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            foreach ([
                'deactivated_at',
                'status',
                'submissions_open',
                'submission_instructions',
                'bio',
            ] as $column) {
                if (Schema::hasColumn('creators', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
