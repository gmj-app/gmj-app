<?php

namespace App\Models;

use App\Enums\CreatorSentiment;
use App\Enums\MetadataScale;
use App\Enums\ReactionStyle;
use Database\Factories\VideoEditorialMetadataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoEditorialMetadata extends Model
{
    /** @use HasFactory<VideoEditorialMetadataFactory> */
    use HasFactory;

    protected $table = 'video_editorial_metadata';

    protected $fillable = ['creator_video_id', 'creator_sentiment', 'reaction_style', 'energy_level', 'technical_depth', 'humor_level', 'cultural_context_level', 'production_notes', 'classified_by_user_id', 'classified_at', 'reviewed_by_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['creator_sentiment' => CreatorSentiment::class, 'reaction_style' => ReactionStyle::class, 'energy_level' => MetadataScale::class, 'technical_depth' => MetadataScale::class, 'humor_level' => MetadataScale::class, 'cultural_context_level' => MetadataScale::class, 'classified_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(CreatorVideo::class, 'creator_video_id');
    }
}
