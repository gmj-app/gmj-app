<?php

use App\Services\YouTubeUrlService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            if (! Schema::hasColumn('recommendations', 'normalized_url')) {
                $table->text('normalized_url')->nullable()->after('youtube_url');
            }

            if (! Schema::hasColumn('recommendations', 'published_normalized_url')) {
                $table->text('published_normalized_url')->nullable()->after('published_reaction_url');
            }

            if (! Schema::hasColumn('recommendations', 'withdrawn_at')) {
                $table->timestamp('withdrawn_at')->nullable()->after('published_metadata');
            }

            if (! Schema::hasColumn('recommendations', 'withdrawn_by_user_id')) {
                $table->foreignId('withdrawn_by_user_id')
                    ->nullable()
                    ->after('withdrawn_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('recommendations', 'withdrawal_reason')) {
                $table->text('withdrawal_reason')->nullable()->after('withdrawn_by_user_id');
            }
        });

        $normalizer = app(YouTubeUrlService::class);

        DB::table('recommendations')
            ->select(['id', 'youtube_url', 'youtube_video_id', 'published_reaction_url', 'published_video_id'])
            ->orderBy('id')
            ->chunkById(100, function ($recommendations) use ($normalizer): void {
                foreach ($recommendations as $recommendation) {
                    $original = $normalizer->normalize($recommendation->youtube_url);
                    $published = $normalizer->normalize($recommendation->published_reaction_url);

                    DB::table('recommendations')
                        ->where('id', $recommendation->id)
                        ->update([
                            'normalized_url' => $original['canonical_url'],
                            'youtube_video_id' => $recommendation->youtube_video_id ?: $original['youtube_video_id'],
                            'published_normalized_url' => $published['canonical_url'],
                            'published_video_id' => $recommendation->published_video_id ?: $published['youtube_video_id'],
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table): void {
            if (Schema::hasColumn('recommendations', 'withdrawn_by_user_id')) {
                $table->dropConstrainedForeignId('withdrawn_by_user_id');
            }

            $columns = collect([
                'normalized_url',
                'published_normalized_url',
                'withdrawn_at',
                'withdrawal_reason',
            ])->filter(
                fn (string $column): bool => Schema::hasColumn('recommendations', $column),
            )->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
