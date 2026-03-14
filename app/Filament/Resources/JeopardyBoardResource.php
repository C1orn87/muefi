<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JeopardyBoardResource\Pages;
use App\Models\JeopardyBoard;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class JeopardyBoardResource extends Resource
{
    protected static ?string $model = JeopardyBoard::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static \UnitEnum|string|null $navigationGroup = 'Games';
    protected static ?string $label = 'Jeopardy Board';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Board Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(120),

                    Textarea::make('description')
                        ->rows(2)
                        ->maxLength(500),

                    Toggle::make('is_public')
                        ->label('Public')
                        ->default(true),

                    Grid::make(2)->schema([
                        TextInput::make('columns')
                            ->label('Categories')
                            ->numeric()
                            ->default(6)
                            ->minValue(1)
                            ->maxValue(10),

                        TextInput::make('rows')
                            ->label('Rows (point tiers)')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(10),
                    ]),
                ]),

            Section::make('Categories & Questions')
                ->description('For full question editing with media uploads, use the frontend builder.')
                ->schema([
                    Repeater::make('categories')
                        ->relationship()
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->label('Category name'),

                            TextInput::make('order')
                                ->numeric()
                                ->default(0)
                                ->label('Order'),

                            Repeater::make('questions')
                                ->relationship()
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('points')
                                            ->numeric()
                                            ->required()
                                            ->default(200),

                                        Select::make('question_type')
                                            ->options([
                                                'text'       => 'Text',
                                                'image'      => 'Image',
                                                'zoom_image' => 'Zoom Image',
                                                'audio'      => 'Audio',
                                                'video'      => 'Video',
                                                'youtube'    => 'YouTube',
                                            ])
                                            ->default('text')
                                            ->required(),

                                        TextInput::make('order')
                                            ->numeric()
                                            ->default(0),
                                    ]),

                                    Textarea::make('question_text')
                                        ->label('Question / Clue')
                                        ->rows(2),

                                    TextInput::make('answer_text')
                                        ->label('Answer'),

                                    TextInput::make('media_url')
                                        ->label('YouTube URL')
                                        ->url()
                                        ->visible(fn (Get $get) => $get('question_type') === 'youtube'),
                                ])
                                ->columns(1)
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string =>
                                    '$'.($state['points'] ?? '?').' — '.($state['question_text'] ?? 'No question')),
                        ])
                        ->columns(1)
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Category'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner')->searchable(),
                Tables\Columns\IconColumn::make('is_public')->label('Public')->boolean(),
                Tables\Columns\TextColumn::make('columns')->label('Categories'),
                Tables\Columns\TextColumn::make('rows')->label('Rows'),
                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('Sessions')
                    ->counts('sessions'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_public')->label('Public'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListJeopardyBoards::route('/'),
            'create' => Pages\CreateJeopardyBoard::route('/create'),
            'edit'   => Pages\EditJeopardyBoard::route('/{record}/edit'),
        ];
    }
}
