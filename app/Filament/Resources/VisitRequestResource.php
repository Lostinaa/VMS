<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitRequestResource\Pages;
use App\Models\VisitRequest;
use App\Models\VisitApproval;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class VisitRequestResource extends Resource
{
    protected static ?string $model = VisitRequest::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Visitor Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Visit Requests';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Visit Details')->schema([
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
                    ->relationship('zone', 'name', fn ($query, Schemas\Components\Utilities\Get $get) =>
                        $query->where('site_id', $get('site_id'))
                    )->searchable()->preload(),
            ])->columns(2),

            Schemas\Components\Section::make('Schedule & Purpose')->schema([
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
                Tables\Columns\TextColumn::make('visitor.full_name')->label('Visitor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('host.name')->label('Host')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->sortable(),
                Tables\Columns\TextColumn::make('purpose')->limit(30)->tooltip(fn ($record) => $record->purpose),
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
                Tables\Columns\TextColumn::make('scheduled_at')->dateTime('M d, Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                    'checked_in' => 'Checked In', 'checked_out' => 'Checked Out',
                ]),
                Tables\Filters\SelectFilter::make('site_id')->relationship('site', 'name')->label('Site'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(function ($record) {
                            $qr = 'VMS-VR-' . $record->id . '-' . now()->timestamp;
                            $record->update(['status' => 'approved', 'qr_code' => $qr]);
                            VisitApproval::create([
                                'visit_request_id' => $record->id,
                                'approver_id' => auth()->id(),
                                'action' => 'approved',
                                'acted_at' => now(),
                            ]);
                            Notification::make()->title('Visit Approved — QR generated')->success()->send();
                        }),
                    \Filament\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('remarks')->label('Rejection Reason')->required(),
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
                \Filament\Actions\Action::make('qr_code')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->label('QR')
                    ->visible(fn ($record) => !empty($record->qr_code))
                    ->url(fn ($record) => route('visit.qr', $record->id))
                    ->openUrlInNewTab(),
                \Filament\Actions\Action::make('badge')
                    ->icon('heroicon-o-identification')
                    ->color('primary')
                    ->label('Badge')
                    ->visible(fn ($record) => in_array($record->status, ['approved', 'checked_in']))
                    ->url(fn ($record) => route('visit.badge', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
