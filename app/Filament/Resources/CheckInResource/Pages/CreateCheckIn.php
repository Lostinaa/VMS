<?php
namespace App\Filament\Resources\CheckInResource\Pages;
use App\Filament\Resources\CheckInResource;
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
}
