<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStatusHistory extends Model
{
    protected $fillable = ['from_status', 'to_status'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
