<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CheckInResource\Pages;
use App\Models\CheckIn;
use App\Notifications\VisitorCheckedInNotification;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class CheckInResource extends Resource
{
    protected static ?string $model = CheckIn::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-end-on-rectangle';
    protected static string|\UnitEnum|null $navigationGroup = 'Visitor Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Check-In / Out';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Check-In Details')->schema([
                Forms\Components\Toggle::make('is_walk_in')
                    ->label('Walk-In Visitor (No Pre-Registration)')
                    ->reactive()
                    ->default(false)
                    ->columnSpanFull()
                    ->helperText('Enable this for visitors who arrive without a pre-registered visit request.'),

                // Existing visit request selection — hidden for walk-ins
                Forms\Components\Select::make('visit_request_id')
                    ->relationship(
                        'visitRequest',
                        'id',
                        fn($query) =>
                        $query->where('status', 'approved')->orWhere('status', 'checked_in')
                    )->getOptionLabelFromRecordUsing(
                        fn($record) =>
                        "#{$record->id} - {$record->visitor->full_name} ({$record->purpose})"
                    )->searchable()->preload()
                    ->required(fn (Forms\Get $get) => !$get('is_walk_in'))
                    ->visible(fn (Forms\Get $get) => !$get('is_walk_in'))
                    ->reactive()
                    ->afterStateUpdated(fn($state, Schemas\Components\Utilities\Set $set) => $set(
                        'visitor_id',
                        \App\Models\VisitRequest::find($state)?->visitor_id
                    )),
                Forms\Components\Hidden::make('visitor_id'),
                Forms\Components\Select::make('checked_in_by')
                    ->relationship('checkedInBy', 'name')->default(auth()->id()),
                Forms\Components\DateTimePicker::make('checked_in_at')
                    ->required()->default(now())->native(false),
                Forms\Components\DateTimePicker::make('checked_out_at')->native(false),
            ])->columns(2),

            // Walk-in visitor details — only shown when is_walk_in is enabled
            Schemas\Components\Section::make('Walk-In Visitor Details')
                ->description('Enter the visitor\'s details for walk-in registration. The system will auto-create the visit request.')
                ->visible(fn (Forms\Get $get) => $get('is_walk_in'))
                ->schema([
                    Forms\Components\TextInput::make('walkin_full_name')
                        ->label('Full Name')
                        ->required(fn (Forms\Get $get) => $get('is_walk_in'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('walkin_email')
                        ->label('Email')->email()->maxLength(255),
                    Forms\Components\TextInput::make('walkin_phone')
                        ->label('Phone')->tel()->maxLength(20),
                    Forms\Components\TextInput::make('walkin_organization')
                        ->label('Organization')->maxLength(255),
                    Forms\Components\Select::make('walkin_id_type')
                        ->label('ID Type')
                        ->options([
                            'national_id' => 'National ID',
                            'passport' => 'Passport',
                            'driving_license' => 'Driving License',
                            'employee_id' => 'Employee ID',
                            'other' => 'Other',
                        ]),
                    Forms\Components\TextInput::make('walkin_id_number')
                        ->label('ID Number')->maxLength(50),
                ])->columns(2),

            Schemas\Components\Section::make('Walk-In Visit Details')
                ->description('Specify the visit details for this walk-in.')
                ->visible(fn (Forms\Get $get) => $get('is_walk_in'))
                ->schema([
                    Forms\Components\Select::make('walkin_host_id')
                        ->label('Host (Employee)')
                        ->relationship('visitRequest.host', 'name', fn($query) => $query->where('is_active', true))
                        ->searchable()->preload()
                        ->required(fn (Forms\Get $get) => $get('is_walk_in')),
                    Forms\Components\Select::make('walkin_site_id')
                        ->label('Site')
                        ->relationship('visitRequest.site', 'name')
                        ->searchable()->preload()
                        ->required(fn (Forms\Get $get) => $get('is_walk_in')),
                    Forms\Components\Select::make('walkin_zone_id')
                        ->label('Zone')
                        ->relationship('visitRequest.zone', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('walkin_department_id')
                        ->label('Department / Destination Unit')
                        ->relationship('visitRequest.department', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('walkin_purpose')
                        ->label('Purpose of Visit')
                        ->required(fn (Forms\Get $get) => $get('is_walk_in'))
                        ->maxLength(500),
                    Forms\Components\Select::make('walkin_visitor_type')
                        ->label('Visitor Type')
                        ->options([
                            'external' => 'External',
                            'internal' => 'Internal',
                        ])->default('external'),
                    Forms\Components\Select::make('walkin_category')
                        ->label('Category')
                        ->options([
                            'general' => 'General',
                            'contractor' => 'Contractor',
                            'government' => 'Government',
                            'vip' => 'VIP',
                            'delivery' => 'Delivery',
                            'interview' => 'Interview',
                        ])->default('general'),
                    Forms\Components\TextInput::make('walkin_expected_duration')
                        ->label('Expected Duration (hours)')
                        ->numeric()->default(1),
                ])->columns(2),

            Schemas\Components\Section::make('Verification')->schema([
                Forms\Components\FileUpload::make('photo_path')
                    ->image()->directory('checkins/photos')->label('Photo'),
                Forms\Components\FileUpload::make('signature_path')
                    ->image()->directory('checkins/signatures')->label('Signature'),
                Forms\Components\TextInput::make('badge_number'),
                Forms\Components\Select::make('escort_id')
                    ->relationship('escort', 'name')
                    ->preload()->searchable()
                    ->label('Escort (FR-008)')
                    ->helperText('Required for restricted zones'),
                Forms\Components\Textarea::make('remarks')->rows(2)->columnSpanFull(),
            ])->columns(2),

            Schemas\Components\Section::make('Documents')->schema([
                Forms\Components\FileUpload::make('checkin_documents')
                    ->label('Supporting Documents (NDA, ID Copy, etc.)')
                    ->multiple()
                    ->directory('checkins/documents')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxFiles(5)
                    ->helperText('Upload NDA, ID scan, authorization letters, etc.'),
            ]),

            Schemas\Components\Section::make('Badge')->schema([
                Forms\Components\Select::make('badge_type')
                    ->label('Badge Type (FR-006)')
                    ->options([
                        'adhesive' => 'Adhesive Sticker',
                        'self_expiring' => 'Self-Expiring',
                        'plastic_card' => 'Plastic Card',
                        'temporary_staff_id' => 'Temporary Staff ID',
                        'gate_pass' => 'Gate Pass',
                    ])
                    ->default('adhesive')
                    ->helperText('Select the type of physical badge to issue'),
            ]),
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
            Tables\Columns\TextColumn::make('escort.name')->label('Escort')->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('checkedInBy.name')->label('By')->toggleable(isToggledHiddenByDefault: true),
        ])
            ->defaultSort('checked_in_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('on_site')
                    ->query(fn($query) => $query->whereNull('checked_out_at'))
                    ->label('Currently On-Site')->default(),
            ])
            ->actions([
                \Filament\Actions\Action::make('checkout')
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn($record) => is_null($record->checked_out_at))
                    ->action(function ($record) {
                        $record->update([
                            'checked_out_at' => now(),
                            'checked_out_by' => auth()->id(),
                        ]);
                        $record->visitRequest->update(['status' => 'checked_out']);
                        Notification::make()->title('Visitor checked out')->success()->send();
                    }),
                \Filament\Actions\Action::make('print_badge')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn ($record) => route('visit.badge', $record->visit_request_id))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->visit_request_id),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()])]);
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
