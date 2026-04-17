<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitorLogResource\Pages;
use App\Models\VisitorLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VisitorLogResource extends Resource
{
    protected static ?string $model = VisitorLog::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static string|\UnitEnum|null $navigationGroup = 'Security';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Movement Logs';

    public static function canCreate(): bool
    {
        return false; // Logs are auto-generated, not manually created
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(VisitorLog::query()->with(['visitor', 'zone', 'checkIn.visitRequest'])->latest('logged_at'))
            ->columns([
                Tables\Columns\TextColumn::make('logged_at')
                    ->label('Time')
                    ->dateTime('M d, H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('visitor.full_name')
                    ->label('Visitor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zone')
                    ->sortable(),
                Tables\Columns\TextColumn::make('access_point')
                    ->label('Access Point'),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entry' => 'success',
                        'exit' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('checkIn.visitRequest.host.name')
                    ->label('Host'),
                Tables\Columns\TextColumn::make('checkIn.visitRequest.purpose')
                    ->label('Purpose')
                    ->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options(['entry' => 'Entry', 'exit' => 'Exit']),
                Tables\Filters\SelectFilter::make('zone_id')
                    ->relationship('zone', 'name')
                    ->label('Zone'),
            ])
            ->defaultSort('logged_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitorLogs::route('/'),
        ];
    }
}
