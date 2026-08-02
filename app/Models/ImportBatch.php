<?php

namespace App\Models;

use App\Enums\ImportBatchSource;
use App\Enums\ImportBatchStatus;
use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    protected $fillable = ['creator_channel_id', 'uploaded_by_user_id', 'source', 'original_filename', 'stored_filename', 'storage_disk', 'storage_path', 'detected_csv_filename', 'snapshot_date', 'status', 'column_mapping', 'detected_columns', 'preview_rows', 'total_rows', 'successful_rows', 'created_rows', 'updated_rows', 'skipped_rows', 'failed_rows', 'started_at', 'completed_at', 'error_summary'];

    protected function casts(): array
    {
        return ['source' => ImportBatchSource::class, 'status' => ImportBatchStatus::class, 'snapshot_date' => 'date', 'column_mapping' => 'array', 'detected_columns' => 'array', 'preview_rows' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (ImportBatch $batch) => Storage::disk($batch->storage_disk)->delete($batch->storage_path));
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CreatorChannel::class, 'creator_channel_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class);
    }
}
