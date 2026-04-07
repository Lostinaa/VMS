<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitApproval extends Model
{
    protected $fillable = ['visit_request_id', 'approver_id', 'action', 'remarks', 'acted_at'];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function visitRequest(): BelongsTo
    {
        return $this->belongsTo(VisitRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
