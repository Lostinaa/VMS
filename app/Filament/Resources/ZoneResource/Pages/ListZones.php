<?php
namespace App\Filament\Resources\ZoneResource\Pages;
use App\Filament\Resources\ZoneResource;
use Filament\Resources\Pages\ListRecords;
class ListZones extends ListRecords
{
    protected static string $resource = ZoneResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
