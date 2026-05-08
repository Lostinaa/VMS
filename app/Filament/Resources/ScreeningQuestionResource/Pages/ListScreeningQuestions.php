<?php

namespace App\Filament\Resources\ScreeningQuestionResource\Pages;

use App\Filament\Resources\ScreeningQuestionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListScreeningQuestions extends ListRecords
{
    protected static string $resource = ScreeningQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
