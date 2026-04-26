<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\Pages;
use App\Models\JobPosition;
use App\Models\Recruitment\RecruitmentEvaluationFormTemplate;
use App\Models\Recruitment\RecruitmentEvaluationCriteria;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class RecruitmentEvaluationFormTemplateResource extends Resource
{
    use BelongsToModule;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = RecruitmentEvaluationFormTemplate::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 5;
    protected static ?string $modelLabel = 'Evaluation Form';
    protected static ?string $pluralModelLabel = 'Evaluation Forms';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('General info')->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Form Name')
                        ->required()
                        ->maxLength(255),
                    
                    \Filament\Forms\Components\Select::make('job_position_id')
                        ->label('Job position')
                        ->options(fn (): array => JobPosition::query()
                            ->orderBy('title')
                            ->pluck('title', 'id')
                            ->all())
                        ->placeholder('All')
                        ->searchable()
                        ->nullable(),
                        
                    \Filament\Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])->columns(2),

                \Filament\Schemas\Components\Section::make('List of evaluation criteria')->schema([
                    \Filament\Forms\Components\Repeater::make('groups')
                        ->label('Criteria Groups')
                        ->schema([
                            \Filament\Forms\Components\Select::make('group_criteria_id')
                                ->label('Group criteria')
                                ->options(fn (): array => static::criteriaOptions('group_criteria'))
                                ->required()
                                ->searchable(),

                            \Filament\Forms\Components\Repeater::make('lines')
                                ->label('Evaluation items')
                                ->schema([
                                    \Filament\Forms\Components\Select::make('criteria_id')
                                        ->label('Evaluation criteria')
                                        ->options(fn (): array => static::criteriaOptions('evaluation_criteria'))
                                        ->required()
                                        ->searchable(),

                                    \Filament\Forms\Components\TextInput::make('proportion')
                                        ->label('Proportion(%)')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(100)
                                        ->required(),
                                ])
                                ->addActionLabel('+ Add criterion')
                                ->minItems(1)
                                ->columns(2),
                        ])
                        ->addActionLabel('+ Add group')
                        ->minItems(1),
                ]),
            ]);
    }

    protected static function criteriaOptions(string $type): array
    {
        $baseQuery = RecruitmentEvaluationCriteria::query()
            ->whereRaw("REPLACE(LOWER(criteria_type), ' ', '_') = ?", [strtolower($type)])
            ->orderBy('name');

        $activeOptions = (clone $baseQuery)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->all();

        if (! empty($activeOptions)) {
            return $activeOptions;
        }

        return (clone $baseQuery)
            ->pluck('name', 'id')
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Added from')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Form Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jobPosition.title')
                    ->label('Position')
                    ->default('All')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lines_count')
                    ->counts('lines')
                    ->label('Number of criteria'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added date')
                    ->date()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecruitmentEvaluationFormTemplates::route('/'),
            'create' => Pages\CreateRecruitmentEvaluationFormTemplate::route('/create'),
            'edit' => Pages\EditRecruitmentEvaluationFormTemplate::route('/{record}/edit'),
        ];
    }
}
