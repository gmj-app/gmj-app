<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeDescriptionBackup extends Model
{
    protected $fillable = [
        'user_id',
        'video_id',
        'video_title',
        'original_description',
        'new_description',
        'operation_batch_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
