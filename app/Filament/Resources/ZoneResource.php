<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZoneResource\Pages;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Select::make('site_id')->relationship('site', 'name')->required()->searchable()->preload(),
            Forms\Components\Select::make('security_level')->options([
                'normal' => 'Normal', 'restricted' => 'Restricted', 'high_security' => 'High Security',
            ])->default('normal')->required(),
            Forms\Components\Textarea::make('description')->rows(2),
            Forms\Components\Toggle::make('escort_required')->default(false),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('site.name')->sortable(),
            Tables\Columns\TextColumn::make('security_level')->badge()->color(fn (string $state) => match ($state) {
                'normal' => 'success', 'restricted' => 'warning', 'high_security' => 'danger',
            }),
            Tables\Columns\IconColumn::make('escort_required')->boolean(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
        ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
            'create' => Pages\CreateZone::route('/create'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
        ];
    }
}
