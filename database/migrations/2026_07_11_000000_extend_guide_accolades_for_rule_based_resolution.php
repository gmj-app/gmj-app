<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_accolades', function (Blueprint $table): void {
            $table->string('short_label')->nullable()->after('label');
            $table->string('rule_type')->nullable()->after('description')->index();
            $table->unsignedInteger('minimum_guide_number')->nullable()->after('rule_type');
            $table->unsignedInteger('maximum_guide_number')->nullable()->after('minimum_guide_number');
            $table->boolean('display_number_plate')->default(false)->after('maximum_guide_number');
            $table->string('plate_prefix')->default('#')->after('display_number_plate');
            $table->string('css_class')->nullable()->after('plate_prefix');
            $table->string('icon')->nullable()->after('css_class');
        });
    }

    public function down(): void
    {
        Schema::table('guide_accolades', function (Blueprint $table): void {
            $table->dropIndex(['rule_type']);
            $table->dropColumn([
                'short_label',
                'rule_type',
                'minimum_guide_number',
                'maximum_guide_number',
                'display_number_plate',
                'plate_prefix',
                'css_class',
                'icon',
            ]);
        });
    }
};
