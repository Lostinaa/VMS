<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlertResource\Pages;
use App\Models\Alert;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';
    protected static string | \UnitEnum | null $navigationGroup = 'Security';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Alert::whereNull('acknowledged_at')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('type')->options([
                'overstay' => 'Overstay', 'violation' => 'Violation',
                'blacklist' => 'Blacklist', 'unauthorized' => 'Unauthorized', 'other' => 'Other',
            ])->required(),
            Forms\Components\Select::make('severity')->options([
                'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical',
            ])->required()->default('medium'),
            Forms\Components\Select::make('visitor_id')->relationship('visitor', 'full_name')->searchable()->preload(),
            Forms\Components\Select::make('visit_request_id')->relationship('visitRequest', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->visitor->full_name}")
                ->searchable()->preload(),
            Forms\Components\Textarea::make('message')->required()->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state) => match ($state) {
                'overstay' => 'warning', 'violation' => 'danger', 'blacklist' => 'danger',
                'unauthorized' => 'danger', default => 'gray',
            }),
            Tables\Columns\TextColumn::make('severity')->badge()->color(fn (string $state) => match ($state) {
                'low' => 'gray', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'danger',
            }),
            Tables\Columns\TextColumn::make('visitor.full_name')->label('Visitor')->searchable(),
            Tables\Columns\TextColumn::make('message')->limit(50),
            Tables\Columns\TextColumn::make('acknowledgedBy.name')->label('Ack By')->placeholder('Pending'),
            Tables\Columns\TextColumn::make('created_at')->dateTime('M d, H:i')->sortable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\Filter::make('unacknowledged')
                ->query(fn ($query) => $query->whereNull('acknowledged_at'))
                ->label('Unacknowledged')->default(),
            Tables\Filters\SelectFilter::make('type')->options([
                'overstay' => 'Overstay', 'violation' => 'Violation', 'blacklist' => 'Blacklist',
            ]),
            Tables\Filters\SelectFilter::make('severity')->options([
                'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical',
            ]),
        ])
        ->actions([
            \Filament\Actions\Action::make('acknowledge')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => is_null($record->acknowledged_at))
                ->action(function ($record) {
                    $record->update(['acknowledged_by' => auth()->id(), 'acknowledged_at' => now()]);
                    Notification::make()->title('Alert acknowledged')->success()->send();
                }),
            \Filament\Actions\ViewAction::make(),
        ])
        ->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlerts::route('/'),
            'create' => Pages\CreateAlert::route('/create'),
        ];
    }
}
