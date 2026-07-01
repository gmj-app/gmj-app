<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            if (! Schema::hasColumn('creators', 'youtube_channel_id')) {
                $table->string('youtube_channel_id')->nullable()->unique()->after('channel_url');
            }

            if (! Schema::hasColumn('creators', 'youtube_channel_title')) {
                $table->string('youtube_channel_title')->nullable()->after('youtube_channel_id');
            }

            if (! Schema::hasColumn('creators', 'youtube_channel_url')) {
                $table->string('youtube_channel_url')->nullable()->after('youtube_channel_title');
            }

            if (! Schema::hasColumn('creators', 'youtube_thumbnail_url')) {
                $table->string('youtube_thumbnail_url')->nullable()->after('youtube_channel_url');
            }

            if (! Schema::hasColumn('creators', 'verification_status')) {
                $table->string('verification_status')->default('unverified')->after('youtube_thumbnail_url');
            }

            if (! Schema::hasColumn('creators', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            foreach ([
                'verified_at',
                'verification_status',
                'youtube_thumbnail_url',
                'youtube_channel_url',
                'youtube_channel_title',
                'youtube_channel_id',
            ] as $column) {
                if (Schema::hasColumn('creators', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
