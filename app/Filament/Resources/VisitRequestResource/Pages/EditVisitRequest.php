<?php
namespace App\Filament\Resources\VisitRequestResource\Pages;
use App\Filament\Resources\VisitRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditVisitRequest extends EditRecord
{
    protected static string $resource = VisitRequestResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
