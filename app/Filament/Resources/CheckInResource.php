<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CheckInResource\Pages;
use App\Models\CheckIn;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class CheckInResource extends Resource
{
    protected static ?string $model = CheckIn::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-right-end-on-rectangle';
    protected static string | \UnitEnum | null $navigationGroup = 'Visitor Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Check-In / Out';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Check-In Details')->schema([
                Forms\Components\Select::make('visit_request_id')
                    ->relationship('visitRequest', 'id', fn ($query) =>
                        $query->where('status', 'approved')->orWhere('status', 'checked_in')
                    )->getOptionLabelFromRecordUsing(fn ($record) =>
                        "#{$record->id} - {$record->visitor->full_name} ({$record->purpose})"
                    )->searchable()->preload()->required()->reactive()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('visitor_id',
                        \App\Models\VisitRequest::find($state)?->visitor_id
                    )),
                Forms\Components\Hidden::make('visitor_id'),
                Forms\Components\Select::make('checked_in_by')
                    ->relationship('checkedInBy', 'name')->default(auth()->id()),
                Forms\Components\DateTimePicker::make('checked_in_at')
                    ->required()->default(now())->native(false),
                Forms\Components\DateTimePicker::make('checked_out_at')->native(false),
            ])->columns(2),

            Forms\Components\Section::make('Verification')->schema([
                Forms\Components\FileUpload::make('photo_path')
                    ->image()->directory('checkins/photos')->label('Photo'),
                Forms\Components\FileUpload::make('signature_path')
                    ->image()->directory('checkins/signatures')->label('Signature'),
                Forms\Components\TextInput::make('badge_number'),
                Forms\Components\Textarea::make('remarks')->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('visitRequest.visitor.full_name')->label('Visitor')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('visitRequest.host.name')->label('Host')->sortable(),
            Tables\Columns\TextColumn::make('visitRequest.site.name')->label('Site')->sortable(),
            Tables\Columns\TextColumn::make('checked_in_at')->dateTime('M d, H:i')->sortable(),
            Tables\Columns\TextColumn::make('checked_out_at')->dateTime('M d, H:i')->sortable()
                ->placeholder('Still on-site')->color('warning'),
            Tables\Columns\TextColumn::make('badge_number')->badge(),
            Tables\Columns\TextColumn::make('checkedInBy.name')->label('By')->toggleable(isToggledHiddenByDefault: true),
        ])
        ->defaultSort('checked_in_at', 'desc')
        ->filters([
            Tables\Filters\Filter::make('on_site')
                ->query(fn ($query) => $query->whereNull('checked_out_at'))
                ->label('Currently On-Site')->default(),
        ])
        ->actions([
            Tables\Actions\Action::make('checkout')
                ->icon('heroicon-o-arrow-left-start-on-rectangle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn ($record) => is_null($record->checked_out_at))
                ->action(function ($record) {
                    $record->update([
                        'checked_out_at' => now(),
                        'checked_out_by' => auth()->id(),
                    ]);
                    $record->visitRequest->update(['status' => 'checked_out']);
                    Notification::make()->title('Visitor checked out')->success()->send();
                }),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCheckIns::route('/'),
            'create' => Pages\CreateCheckIn::route('/create'),
            'edit' => Pages\EditCheckIn::route('/{record}/edit'),
        ];
    }
}
