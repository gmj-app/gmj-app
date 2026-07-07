<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'public_display_name')) {
                $table->string('public_display_name', 40)->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'public_handle')) {
                $table->string('public_handle', 30)->nullable()->unique()->after('public_display_name');
            }

            if (! Schema::hasColumn('users', 'public_profile_completed_at')) {
                $table->timestamp('public_profile_completed_at')->nullable()->after('public_handle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'public_handle')) {
                $table->dropUnique(['public_handle']);
            }

            $columns = collect([
                'public_display_name',
                'public_handle',
                'public_profile_completed_at',
            ])->filter(
                fn (string $column): bool => Schema::hasColumn('users', $column),
            )->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
