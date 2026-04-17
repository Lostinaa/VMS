<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CheckIn extends Model
{
    protected $fillable = [
        'visit_request_id', 'visitor_id', 'checked_in_by',
        'checked_in_at', 'checked_out_at', 'checked_out_by',
        'photo_path', 'signature_path', 'badge_number',
        'qr_code', 'checked_in_via_qr', 'remarks',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function visitRequest(): BelongsTo
    {
        return $this->belongsTo(VisitRequest::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function badge(): HasOne
    {
        return $this->hasOne(Badge::class);
    }
}
