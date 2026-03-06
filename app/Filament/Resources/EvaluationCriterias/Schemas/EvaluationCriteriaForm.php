<?php

namespace App\Filament\Resources\EvaluationCriterias\Schemas;

use Filament\Forms\Components\Grid as ComponentsGrid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class EvaluationCriteriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('criteria_name')
                    ->label('Criteria Name')
                    ->required(),

                Select::make('criteria_type')
                    ->label('Criteria Type')
                    ->options([
                        'group criteria' => 'Group Criteria',
                        'criteria' => 'Criteria',
                    ])
                    ->placeholder('Select type')
                    ->default(null)
                    ->required(),


                Repeater::make('scores')
                    ->label('Score Descriptions')
                    ->relationship('scores')
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('score')
                                ->label(false)
                                ->readOnly()
                                ->numeric()
                                ->columnSpan(2),
                            Textarea::make('description')
                                ->label(false)
                                ->rows(2)
                                ->columnSpan(10),
                        ]),
                    ])
                    ->default([
                        ['score' => 1],
                        ['score' => 2],
                        ['score' => 3],
                        ['score' => 4],
                        ['score' => 5],
                    ])
                    ->reorderable(false)
                    ->addable(false)
                    ->deletable(false),


                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),

            ]);
    }
}
