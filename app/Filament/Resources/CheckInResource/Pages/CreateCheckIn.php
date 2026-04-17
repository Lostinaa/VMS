<?php

namespace App\Filament\Resources\CheckInResource\Pages;

use App\Filament\Resources\CheckInResource;
use App\Models\VisitorLog;
use App\Notifications\VisitorCheckedInNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateCheckIn extends CreateRecord
{
    protected static string $resource = CheckInResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['visit_request_id'])) {
            $vr = \App\Models\VisitRequest::find($data['visit_request_id']);
            if ($vr) {
                $data['visitor_id'] = $vr->visitor_id;
                $vr->update(['status' => 'checked_in']);
            }
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
    }
}

