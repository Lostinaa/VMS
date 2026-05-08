<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Department;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('User Information')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                Forms\Components\TextInput::make('employee_id')->unique(ignoreRecord: true)->maxLength(50),
                Forms\Components\TextInput::make('password')->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ])->columns(2),

            Schemas\Components\Section::make('Role & Assignment')->schema([
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'host' => 'Host / Employee',
                        'receptionist' => 'Receptionist',
                        'security' => 'Security',
                        'cxo_pa' => 'CXO PA',
                    ])->required()->default('host'),
                Forms\Components\Select::make('site_id')
                    ->relationship('site', 'name')
                    ->preload()->searchable(),
                Forms\Components\Select::make('department_id')
                    ->relationship('department', 'name')
                    ->preload()->searchable(),
                Forms\Components\Select::make('supervisor_id')
                    ->relationship('supervisor', 'name')
                    ->preload()->searchable()
                    ->label('Supervisor')
                    ->helperText('Used for internal visitor approval routing (FR-002)'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('role')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'host' => 'info',
                        'receptionist' => 'success',
                        'security' => 'warning',
                        'cxo_pa' => 'gray',
                        default => 'gray',
                    })->sortable(),
                Tables\Columns\TextColumn::make('site.name')->sortable(),
                Tables\Columns\TextColumn::make('department.name')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'host' => 'Host',
                        'receptionist' => 'Receptionist',
                        'security' => 'Security',
                        'cxo_pa' => 'CXO PA',
                    ]),
                Tables\Filters\SelectFilter::make('site_id')
                    ->relationship('site', 'name')->label('Site'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
