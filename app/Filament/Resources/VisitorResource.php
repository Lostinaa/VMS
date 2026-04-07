<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitorResource\Pages;
use App\Models\Visitor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Visitor Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal Information')->schema([
                Forms\Components\TextInput::make('full_name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->maxLength(255),
                Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                Forms\Components\TextInput::make('organization')->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Identification')->schema([
                Forms\Components\Select::make('id_type')
                    ->options([
                        'national_id' => 'National ID',
                        'passport' => 'Passport',
                        'drivers_license' => 'Driver\'s License',
                        'employee_id' => 'Employee ID',
                        'other' => 'Other',
                    ]),
                Forms\Components\TextInput::make('id_number')->maxLength(100),
                Forms\Components\FileUpload::make('photo')
                    ->image()->directory('visitors/photos')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('300')
                    ->imageResizeTargetHeight('300'),
            ])->columns(2),

            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Toggle::make('is_blacklisted')
                    ->label('Blacklisted')
                    ->reactive(),
                Forms\Components\Textarea::make('blacklist_reason')
                    ->visible(fn (Forms\Get $get) => $get('is_blacklisted'))
                    ->required(fn (Forms\Get $get) => $get('is_blacklisted')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->circular()->defaultImageUrl(
                    fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=0D8ABC&color=fff'
                ),
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('organization')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('id_type')->badge(),
                Tables\Columns\IconColumn::make('is_blacklisted')->boolean()
                    ->trueIcon('heroicon-o-x-circle')->trueColor('danger')
                    ->falseIcon('heroicon-o-check-circle')->falseColor('success')
                    ->label('Blacklisted'),
                Tables\Columns\TextColumn::make('visit_requests_count')
                    ->counts('visitRequests')->label('Visits'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_blacklisted')->label('Blacklisted'),
                Tables\Filters\SelectFilter::make('id_type')
                    ->options([
                        'national_id' => 'National ID',
                        'passport' => 'Passport',
                        'drivers_license' => 'Driver\'s License',
                        'employee_id' => 'Employee ID',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListVisitors::route('/'),
            'create' => Pages\CreateVisitor::route('/create'),
            'edit' => Pages\EditVisitor::route('/{record}/edit'),
        ];
    }
}
