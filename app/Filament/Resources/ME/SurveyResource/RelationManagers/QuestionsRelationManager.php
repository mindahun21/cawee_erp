<?php

namespace App\Filament\Resources\ME\SurveyResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Questions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('question_text')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('question_type')
                    ->required()
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'choice' => 'Choice',
                        'multi_choice' => 'Multi Choice',
                        'rating' => 'Rating',
                    ]),
                Toggle::make('is_required')
                    ->default(false),
                Textarea::make('options')
                    ->label('Options (JSON)')
                    ->helperText('Use JSON array for choice/multi_choice/rating questions.')
                    ->formatStateUsing(fn ($state): ?string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                    ->dehydrateStateUsing(function ($state): ?array {
                        if (blank($state)) {
                            return null;
                        }

                        return json_decode((string) $state, true);
                    })
                    ->rules(['nullable', 'json'])
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question_text')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('question_type')
                    ->badge(),
                IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
