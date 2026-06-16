<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Notifications\VisitApprovedNotification;
use App\Notifications\VisitRejectedNotification;

class VisitRequest extends Model
{
    protected $fillable = [
        'visitor_id', 'host_id', 'site_id', 'zone_id', 'department_id', 'meeting_location',
        'purpose', 'visitor_type', 'category', 'status', 'scheduled_at',
        'expected_duration_hours', 'expires_at', 'group_id', 'notes',
        'qr_code', 'parking_number',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (VisitRequest $visitRequest) {
            if ($visitRequest->wasChanged('status')) {
                $status = $visitRequest->status;

                if ($status === 'approved') {
                    // 1. Generate QR code if empty
                    if (empty($visitRequest->qr_code)) {
                        $qr = 'VMS-QR-' . str_pad($visitRequest->id, 6, '0', STR_PAD_LEFT) . '-' . \Illuminate\Support\Str::random(8);
                        $visitRequest->qr_code = $qr;
                        $visitRequest->saveQuietly();
                    }

                    // 2. Create VisitApproval if not exists
                    if (!$visitRequest->approvals()->where('action', 'approved')->exists()) {
                        VisitApproval::create([
                            'visit_request_id' => $visitRequest->id,
                            'approver_id' => auth()->id() ?: 1,
                            'action' => 'approved',
                            'acted_at' => now(),
                        ]);
                    }

                    // 3. Send notification to visitor
                    $visitRequest->load(['visitor', 'host', 'site', 'zone']);
                    if ($visitRequest->visitor) {
                        $visitRequest->visitor->notify(new VisitApprovedNotification($visitRequest));
                    }
                } elseif ($status === 'rejected') {
                    // 1. Fetch remarks from the most recent rejected approval if exists
                    $existingApproval = $visitRequest->approvals()
                        ->where('action', 'rejected')
                        ->latest()
                        ->first();

                    $remarks = $existingApproval ? $existingApproval->remarks : ($visitRequest->notes ?: 'Rejected');

                    // 2. Create VisitApproval if not exists
                    if (!$existingApproval) {
                        $existingApproval = VisitApproval::create([
                            'visit_request_id' => $visitRequest->id,
                            'approver_id' => auth()->id() ?: 1,
                            'action' => 'rejected',
                            'remarks' => $remarks,
                            'acted_at' => now(),
                        ]);
                    }

                    // 3. Send notification to visitor
                    $visitRequest->load(['visitor', 'host', 'site', 'zone']);
                    if ($visitRequest->visitor) {
                        $visitRequest->visitor->notify(new VisitRejectedNotification($visitRequest, $remarks));
                    }
                }
            }
        });
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(VisitApproval::class);
    }

    public function checkIn(): HasOne
    {
        return $this->hasOne(CheckIn::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}

