<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->index();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('storage_disk');
            $table->string('storage_path', 1024);
            $table->string('detected_csv_filename')->nullable();
            $table->date('snapshot_date')->index();
            $table->string('status')->index();
            $table->json('column_mapping')->nullable();
            $table->json('detected_columns')->nullable();
            $table->json('preview_rows')->nullable();
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('successful_rows')->default(0);
            $table->unsignedBigInteger('created_rows')->default(0);
            $table->unsignedBigInteger('updated_rows')->default(0);
            $table->unsignedBigInteger('skipped_rows')->default(0);
            $table->unsignedBigInteger('failed_rows')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->longText('error_summary')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('import_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('row_number');
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->string('status')->index();
            $table->foreignId('creator_video_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('video_performance_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->unique(['import_batch_id', 'row_number'], 'import_batch_row_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_rows');
        Schema::dropIfExists('import_batches');
    }
};
