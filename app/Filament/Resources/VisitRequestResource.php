<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitRequestResource\Pages;
use App\Models\VisitRequest;
use App\Models\VisitApproval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class VisitRequestResource extends Resource
{
    protected static ?string $model = VisitRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Visitor Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Visit Requests';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Visit Details')->schema([
                Forms\Components\Select::make('visitor_id')
                    ->relationship('visitor', 'full_name')
                    ->searchable()->preload()->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('full_name')->required(),
                        Forms\Components\TextInput::make('email')->email(),
                        Forms\Components\TextInput::make('phone')->tel(),
                        Forms\Components\TextInput::make('organization'),
                    ]),
                Forms\Components\Select::make('host_id')
                    ->relationship('host', 'name')
                    ->searchable()->preload()->required(),
                Forms\Components\Select::make('site_id')
                    ->relationship('site', 'name')
                    ->searchable()->preload()->required()->reactive(),
                Forms\Components\Select::make('zone_id')
                    ->relationship('zone', 'name', fn ($query, Forms\Get $get) =>
                        $query->where('site_id', $get('site_id'))
                    )->searchable()->preload(),
            ])->columns(2),

            Forms\Components\Section::make('Schedule & Purpose')->schema([
                Forms\Components\TextInput::make('purpose')->required()->maxLength(255),
                Forms\Components\Select::make('visitor_type')
                    ->options([
                        'external' => 'External Visitor',
                        'internal' => 'Internal (Employee)',
                    ])->default('external')->required(),
                Forms\Components\Select::make('category')
                    ->options([
                        'general' => 'General',
                        'contractor' => 'Contractor',
                        'vendor' => 'Vendor',
                        'vip' => 'VIP',
                        'job_applicant' => 'Job Applicant',
                        'other' => 'Other',
                    ])->default('general'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                        'checked_in' => 'Checked In',
                        'checked_out' => 'Checked Out',
                        'expired' => 'Expired',
                    ])->default('pending')
                    ->visibleOn('edit'),
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->required()->native(false),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->native(false),
                Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('visitor.full_name')
                    ->label('Visitor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('host.name')
                    ->label('Host')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')->sortable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->limit(30)->tooltip(fn ($record) => $record->purpose),
                Tables\Columns\TextColumn::make('visitor_type')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'external' => 'info',
                        'internal' => 'success',
                    }),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        'checked_in' => 'info',
                        'checked_out' => 'primary',
                        'expired' => 'danger',
                        default => 'gray',
                    })->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime('M d, Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'checked_in' => 'Checked In',
                        'checked_out' => 'Checked Out',
                    ]),
                Tables\Filters\SelectFilter::make('visitor_type')
                    ->options([
                        'external' => 'External',
                        'internal' => 'Internal',
                    ]),
                Tables\Filters\SelectFilter::make('site_id')
                    ->relationship('site', 'name')->label('Site'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(function ($record) {
                            $record->update(['status' => 'approved']);
                            VisitApproval::create([
                                'visit_request_id' => $record->id,
                                'approver_id' => auth()->id(),
                                'action' => 'approved',
                                'acted_at' => now(),
                            ]);
                            Notification::make()->title('Visit Approved')->success()->send();
                        }),
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('remarks')
                                ->label('Rejection Reason')
                                ->required(),
                        ])
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(function ($record, array $data) {
                            $record->update(['status' => 'rejected']);
                            VisitApproval::create([
                                'visit_request_id' => $record->id,
                                'approver_id' => auth()->id(),
                                'action' => 'rejected',
                                'remarks' => $data['remarks'],
                                'acted_at' => now(),
                            ]);
                            Notification::make()->title('Visit Rejected')->danger()->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitRequests::route('/'),
            'create' => Pages\CreateVisitRequest::route('/create'),
            'edit' => Pages\EditVisitRequest::route('/{record}/edit'),
        ];
    }
}
