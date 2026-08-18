<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDuplicateReport extends Model
{
    protected $fillable = ['case_id', 'reported_by_user_id', 'note'];

    public function duplicateCase(): BelongsTo
    {
        return $this->belongsTo(RequestDuplicateCase::class, 'case_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}
