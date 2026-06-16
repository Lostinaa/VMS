<?php

namespace App\Filament\Resources\CheckInResource\Pages;

use App\Filament\Resources\CheckInResource;
use App\Models\VisitorLog;
use App\Notifications\VisitorCheckedInNotification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Visitor;
use App\Models\VisitRequest;
use Illuminate\Support\Str;

class CreateCheckIn extends CreateRecord
{
    protected static string $resource = CheckInResource::class;

    public array $walkinDocuments = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['is_walk_in'])) {
            // Find or create Visitor
            $visitor = null;
            if (!empty($data['walkin_email'])) {
                $visitor = Visitor::where('email', $data['walkin_email'])->first();
            }
            if (!$visitor && !empty($data['walkin_phone'])) {
                $visitor = Visitor::where('phone', $data['walkin_phone'])->first();
            }
            if (!$visitor) {
                $visitor = Visitor::create([
                    'full_name' => $data['walkin_full_name'],
                    'email' => $data['walkin_email'] ?? null,
                    'phone' => $data['walkin_phone'] ?? null,
                    'organization' => $data['walkin_organization'] ?? null,
                    'id_type' => $data['walkin_id_type'] ?? null,
                    'id_number' => $data['walkin_id_number'] ?? null,
                ]);
            } else {
                $visitor->update(array_filter([
                    'organization' => $visitor->organization ?: ($data['walkin_organization'] ?? null),
                    'id_type' => $visitor->id_type ?: ($data['walkin_id_type'] ?? null),
                    'id_number' => $visitor->id_number ?: ($data['walkin_id_number'] ?? null),
                ]));
            }

            // Check if visitor is blacklisted
            if ($visitor->is_blacklisted) {
                \App\Models\Alert::create([
                    'type' => 'blacklist',
                    'severity' => 'critical',
                    'visitor_id' => $visitor->id,
                    'message' => "Blacklisted visitor {$visitor->full_name} attempted walk-in check-in.",
                ]);

                Notification::make()
                    ->title('Check-in Denied')
                    ->body('This visitor is blacklisted. Security has been notified.')
                    ->danger()
                    ->send();

                throw new \Filament\Support\Exceptions\Halt();
            }

            // Auto-create approved VisitRequest
            $visitRequest = VisitRequest::create([
                'visitor_id' => $visitor->id,
                'host_id' => $data['walkin_host_id'],
                'site_id' => $data['walkin_site_id'],
                'zone_id' => $data['walkin_zone_id'] ?? null,
                'department_id' => $data['walkin_department_id'] ?? null,
                'purpose' => $data['walkin_purpose'],
                'visitor_type' => $data['walkin_visitor_type'] ?? 'external',
                'category' => $data['walkin_category'] ?? 'general',
                'status' => 'checked_in',
                'scheduled_at' => now(),
                'expected_duration_hours' => $data['walkin_expected_duration'] ?? 1,
            ]);

            // Generate QR code for the visit
            $qrCode = 'VMS-QR-' . str_pad($visitRequest->id, 6, '0', STR_PAD_LEFT) . '-' . Str::random(8);
            $visitRequest->update(['qr_code' => $qrCode]);

            // Set visitor_id and visit_request_id for CheckIn creation
            $data['visitor_id'] = $visitor->id;
            $data['visit_request_id'] = $visitRequest->id;
        } else {
            if (isset($data['visit_request_id'])) {
                $vr = VisitRequest::find($data['visit_request_id']);
                if ($vr) {
                    $data['visitor_id'] = $vr->visitor_id;
                    $vr->update(['status' => 'checked_in']);
                }
            }
        }

        // Extract checkin documents for afterCreate (both walk-in and pre-registered)
        if (!empty($data['checkin_documents'])) {
            $this->walkinDocuments = is_array($data['checkin_documents']) ? $data['checkin_documents'] : [$data['checkin_documents']];
        }

        // Remove all walk-in keys and form fields that are not in the CheckIn table schema
        $keysToUnset = [
            'is_walk_in',
            'walkin_full_name',
            'walkin_email',
            'walkin_phone',
            'walkin_organization',
            'walkin_id_type',
            'walkin_id_number',
            'walkin_host_id',
            'walkin_site_id',
            'walkin_zone_id',
            'walkin_department_id',
            'walkin_purpose',
            'walkin_visitor_type',
            'walkin_category',
            'walkin_expected_duration',
            'checkin_documents',
        ];

        foreach ($keysToUnset as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $checkIn = $this->record;
        $checkIn->load(['visitRequest.visitor', 'visitRequest.host', 'visitRequest.site', 'visitRequest.zone']);
        $visitRequest = $checkIn->visitRequest;

        if ($visitRequest) {
            // Notify host that visitor has arrived (FR-007)
            $host = $visitRequest->host;
            if ($host) {
                $host->notify(new VisitorCheckedInNotification($visitRequest));
            }

            // Auto-create visitor movement log entry (FR-010)
            if ($visitRequest->zone_id) {
                VisitorLog::create([
                    'visitor_id' => $checkIn->visitor_id,
                    'check_in_id' => $checkIn->id,
                    'zone_id' => $visitRequest->zone_id,
                    'access_point' => 'Main Entrance',
                    'action' => 'entry',
                    'logged_at' => now(),
                ]);
            }
        }

        // Store supporting documents (FR-005 / Step 6)
        if (!empty($this->walkinDocuments)) {
            foreach ($this->walkinDocuments as $filePath) {
                \App\Models\CheckInDocument::create([
                    'check_in_id' => $checkIn->id,
                    'file_path' => $filePath,
                    'file_name' => basename($filePath),
                    'document_type' => 'other',
                ]);
            }
        }
    }
}
