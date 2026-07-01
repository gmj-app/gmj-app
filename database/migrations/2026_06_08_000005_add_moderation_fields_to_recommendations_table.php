<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            if (! Schema::hasColumn('recommendations', 'scheduled_for')) {
                $table->dateTime('scheduled_for')->nullable();
            }

            if (! Schema::hasColumn('recommendations', 'published_at')) {
                $table->dateTime('published_at')->nullable();
            }

            if (! Schema::hasColumn('recommendations', 'moderation_reason')) {
                $table->string('moderation_reason')->nullable();
            }

            if (! Schema::hasColumn('recommendations', 'moderation_note')) {
                $table->text('moderation_note')->nullable();
            }

            if (! Schema::hasColumn('recommendations', 'moderated_by')) {
                $table->foreignId('moderated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('recommendations', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable();
            }

            if (! Schema::hasColumn('recommendations', 'published_reaction_url')) {
                $table->string('published_reaction_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            if (Schema::hasColumn('recommendations', 'moderated_by')) {
                $table->dropConstrainedForeignId('moderated_by');
            }

            $columns = collect([
                'scheduled_for',
                'published_at',
                'moderation_reason',
                'moderation_note',
                'moderated_at',
            ])->filter(
                fn (string $column): bool => Schema::hasColumn('recommendations', $column),
            )->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
