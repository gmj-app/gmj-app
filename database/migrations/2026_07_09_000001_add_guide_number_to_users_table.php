<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'guide_number')) {
                $table->unsignedInteger('guide_number')->nullable()->unique()->after('id');
            }
        });

        $nextGuideNumber = 1;

        DB::table('users')
            ->whereNull('guide_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->select(['id'])
            ->chunk(100, function ($users) use (&$nextGuideNumber): void {
                foreach ($users as $user) {
                    while (DB::table('users')->where('guide_number', $nextGuideNumber)->exists()) {
                        $nextGuideNumber++;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->whereNull('guide_number')
                        ->update(['guide_number' => $nextGuideNumber]);

                    $nextGuideNumber++;
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'guide_number')) {
                $table->dropUnique(['guide_number']);
                $table->dropColumn('guide_number');
            }
        });
    }
};
