<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            if (! Schema::hasColumn('creators', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('youtube_thumbnail_url');
            }

            if (! Schema::hasColumn('creators', 'youtube_banner_url')) {
                $table->string('youtube_banner_url')->nullable()->after('avatar_path');
            }

            if (! Schema::hasColumn('creators', 'hero_path')) {
                $table->string('hero_path')->nullable()->after('youtube_banner_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            foreach (['hero_path', 'youtube_banner_url', 'avatar_path'] as $column) {
                if (Schema::hasColumn('creators', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
