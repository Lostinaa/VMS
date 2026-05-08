<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScreeningQuestionResource\Pages;
use App\Models\ScreeningQuestion;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScreeningQuestionResource extends Resource
{
    protected static ?string $model = ScreeningQuestion::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationLabel = 'Screening Questions';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Question Details')->schema([
                Forms\Components\TextInput::make('question_text')
                    ->label('Question (English)')
                    ->required()
                    ->maxLength(500)
                    ->placeholder('e.g. Do you have any symptoms of fever, cough, or cold?')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('question_text_am')
                    ->label('Question (Amharic)')
                    ->maxLength(500)
                    ->placeholder('e.g. የትኩሳት፣ ሳል ወይም ጉንፋን ምልክቶች አሉዎት?')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options([
                        'yes_no' => 'Yes / No',
                        'text' => 'Free Text',
                        'select' => 'Multiple Choice',
                    ])
                    ->default('yes_no')
                    ->required()
                    ->reactive(),
                Forms\Components\TagsInput::make('options')
                    ->label('Options (for multiple choice)')
                    ->placeholder('Add option...')
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === 'select'),
                Forms\Components\TextInput::make('flag_answer')
                    ->label('Flag Answer')
                    ->helperText('If the visitor gives this answer, a security alert is triggered (e.g. "yes" for a fever question).')
                    ->placeholder('e.g. yes'),
            ])->columns(2),

            Schemas\Components\Section::make('Settings')->schema([
                Forms\Components\Select::make('applies_to')
                    ->options([
                        'all' => 'All Visitors',
                        'external' => 'External Only',
                        'internal' => 'Internal Only',
                        'vip' => 'VIP Only',
                    ])
                    ->default('all')
                    ->required(),
                Forms\Components\Toggle::make('is_required')
                    ->label('Required')
                    ->default(true),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('question_text')->label('Question')->limit(60)->searchable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'yes_no' => 'info',
                        'text' => 'warning',
                        'select' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('applies_to')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'all' => 'success',
                        'external' => 'warning',
                        'internal' => 'info',
                        'vip' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_required')->boolean()->label('Required'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('flag_answer')->label('Flags')->placeholder('—'),
            ])
            ->defaultSort('sort_order')
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
            'index' => Pages\ListScreeningQuestions::route('/'),
            'create' => Pages\CreateScreeningQuestion::route('/create'),
            'edit' => Pages\EditScreeningQuestion::route('/{record}/edit'),
        ];
    }
}
