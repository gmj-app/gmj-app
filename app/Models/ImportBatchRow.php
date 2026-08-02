<?php

namespace App\Models;

use App\Enums\ImportRowStatus;
use Database\Factories\ImportBatchRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchRow extends Model
{
    /** @use HasFactory<ImportBatchRowFactory> */
    use HasFactory;

    protected $fillable = ['import_batch_id', 'row_number', 'raw_data', 'normalized_data', 'status', 'creator_video_id', 'video_performance_snapshot_id', 'message'];

    protected function casts(): array
    {
        return ['raw_data' => 'array', 'normalized_data' => 'array', 'status' => ImportRowStatus::class];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(CreatorVideo::class, 'creator_video_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(VideoPerformanceSnapshot::class, 'video_performance_snapshot_id');
    }
}
