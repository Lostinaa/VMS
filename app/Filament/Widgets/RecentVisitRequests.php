<?php

namespace App\Filament\Widgets;

use App\Models\VisitRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentVisitRequests extends BaseWidget
{
    protected static ?string $heading = 'Recent Visit Requests';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(VisitRequest::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('visitor.full_name')->label('Visitor')->searchable(),
                Tables\Columns\TextColumn::make('host.name')->label('Host'),
                Tables\Columns\TextColumn::make('site.name')->label('Site'),
                Tables\Columns\TextColumn::make('purpose')->limit(30),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'checked_in' => 'info',
                        'checked_out' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')->dateTime('M d, H:i'),
            ])
            ->paginated(false);
    }
}
