<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            if (! Schema::hasColumn('recommendations', 'published_title')) {
                $table->string('published_title')->nullable()->after('published_reaction_url');
            }

            if (! Schema::hasColumn('recommendations', 'published_channel')) {
                $table->string('published_channel')->nullable()->after('published_title');
            }

            if (! Schema::hasColumn('recommendations', 'published_thumbnail_url')) {
                $table->text('published_thumbnail_url')->nullable()->after('published_channel');
            }

            if (! Schema::hasColumn('recommendations', 'published_video_id')) {
                $table->string('published_video_id')->nullable()->after('published_thumbnail_url');
            }

            if (! Schema::hasColumn('recommendations', 'published_metadata')) {
                $table->json('published_metadata')->nullable()->after('published_video_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            $columns = collect([
                'published_title',
                'published_channel',
                'published_thumbnail_url',
                'published_video_id',
                'published_metadata',
            ])->filter(
                fn (string $column): bool => Schema::hasColumn('recommendations', $column),
            )->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
