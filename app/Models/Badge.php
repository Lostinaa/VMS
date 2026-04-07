<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Badge extends Model
{
    protected $fillable = [
        'check_in_id', 'visitor_id', 'badge_type',
        'access_level', 'printed_at', 'expires_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }
}
