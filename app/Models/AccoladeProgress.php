<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccoladeProgress extends Model
{
    protected $table = 'accolade_progress';

    protected $fillable = ['subject_type', 'subject_id', 'track', 'current_value', 'next_accolade_key', 'evaluated_at', 'metadata'];

    protected function casts(): array
    {
        return ['current_value' => 'integer', 'evaluated_at' => 'datetime', 'metadata' => 'array'];
    }
}
