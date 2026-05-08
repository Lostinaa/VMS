<?php

namespace App\Filament\Resources\ScreeningQuestionResource\Pages;

use App\Filament\Resources\ScreeningQuestionResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditScreeningQuestion extends EditRecord
{
    protected static string $resource = ScreeningQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
