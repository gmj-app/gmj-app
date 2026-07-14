<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->timestamp('resolved_at')->nullable()->index()->after('published_at');
            $table->text('public_resolution_note')->nullable()->after('resolved_at');
            $table->text('private_resolution_reason')->nullable()->after('public_resolution_note');
            $table->string('prior_coverage_url', 2048)->nullable()->after('private_resolution_reason');
            $table->string('prior_coverage_title')->nullable()->after('prior_coverage_url');
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $table->dropIndex(['resolved_at']);
            $table->dropColumn([
                'resolved_at',
                'public_resolution_note',
                'private_resolution_reason',
                'prior_coverage_url',
                'prior_coverage_title',
            ]);
        });
    }
};
