<?php

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\SurveyResource\Pages\CreateSurvey;
use App\Filament\Resources\ME\SurveyResource\Pages\EditSurvey;
use App\Filament\Resources\ME\SurveyResource\Pages\ListSurveys;
use App\Filament\Resources\ME\SurveyResource\Pages\ViewSurvey;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeSurvey;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SurveyResource extends Resource
{
    protected static ?string $model = MeSurvey::class;
    
    protected static ?string $modelLabel = 'Survey';
    
    protected static ?string $pluralModelLabel = 'Surveys';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Surveys';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Survey')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')
                            ->required()
                            ->options([
                                'baseline' => 'Baseline',
                                'midline' => 'Midline',
                                'endline' => 'Endline',
                                'weekly' => 'Weekly',
                            ]),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('period_start')
                            ->required(),
                        DatePicker::make('period_end')
                            ->required()
                            ->afterOrEqual('period_start'),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Survey')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('title'),
                        TextEntry::make('period_start')
                            ->date(),
                        TextEntry::make('period_end')
                            ->date(),
                        IconEntry::make('is_active')
                            ->boolean(),
                    ]),
                MeAuditTrail::section('me_surveys'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions'),
                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Responses'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SurveyResource\RelationManagers\QuestionsRelationManager::class,
            SurveyResource\RelationManagers\ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveys::route('/'),
            'create' => CreateSurvey::route('/create'),
            'view' => ViewSurvey::route('/{record}'),
            'edit' => EditSurvey::route('/{record}/edit'),
        ];
    }
}
