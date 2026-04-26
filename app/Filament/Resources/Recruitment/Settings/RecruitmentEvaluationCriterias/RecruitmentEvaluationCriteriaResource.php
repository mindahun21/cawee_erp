<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\Pages;
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
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToModule;

class RecruitmentEvaluationCriteriaResource extends Resource
{
    use BelongsToModule;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = RecruitmentEvaluationCriteria::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-list-bullet';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Evaluation Criteria';
    protected static ?string $pluralModelLabel = 'Evaluation Criterias';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\Select::make('criteria_type')
                    ->options([
                        'group_criteria'      => 'Group criteria',
                        'evaluation_criteria' => 'Evaluation criteria',
                    ])
                    ->required()
                    ->live(),
                
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                
                \Filament\Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Scores')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\Placeholder::make('score_label_1')->content('Score 1'),
                            \Filament\Forms\Components\TextInput::make('score_1_desc')
                                ->placeholder('Write a score description for this criterion')
                                ->maxLength(255),
                            
                            \Filament\Forms\Components\Placeholder::make('score_label_2')->content('Score 2'),
                            \Filament\Forms\Components\TextInput::make('score_2_desc')
                                ->placeholder('Write a score description for this criterion')
                                ->maxLength(255),
                                
                            \Filament\Forms\Components\Placeholder::make('score_label_3')->content('Score 3'),
                            \Filament\Forms\Components\TextInput::make('score_3_desc')
                                ->placeholder('Write a score description for this criterion')
                                ->maxLength(255),
                                
                            \Filament\Forms\Components\Placeholder::make('score_label_4')->content('Score 4'),
                            \Filament\Forms\Components\TextInput::make('score_4_desc')
                                ->placeholder('Write a score description for this criterion')
                                ->maxLength(255),
                                
                            \Filament\Forms\Components\Placeholder::make('score_label_5')->content('Score 5'),
                            \Filament\Forms\Components\TextInput::make('score_5_desc')
                                ->placeholder('Write a score description for this criterion')
                                ->maxLength(255),
                        ]),
                    ])
                    ->hidden(fn (Get $get) => $get('criteria_type') !== 'evaluation_criteria'),

                \Filament\Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
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
                    ->label('Criteria name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('criteria_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'group_criteria' => 'info',
                        'evaluation_criteria' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'group_criteria' => 'Group criteria',
                        'evaluation_criteria' => 'Evaluation criteria',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added date')
                    ->date()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('criteria_type')
                    ->options([
                        'group_criteria' => 'Group criteria',
                        'evaluation_criteria' => 'Evaluation criteria',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (RecruitmentEvaluationCriteria $record) {
                        if ($record->templateLinesWhereGroup()->exists() || $record->templateLinesWhereCriteria()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Cannot delete')
                                ->body('This criterion is used in existing evaluation form templates and cannot be deleted. Please deactivate it instead.')
                                ->send();
                            return;
                        }
                        $record->delete();
                    }),
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
            'index' => Pages\ListRecruitmentEvaluationCriterias::route('/'),
            'create' => Pages\CreateRecruitmentEvaluationCriteria::route('/create'),
            'edit' => Pages\EditRecruitmentEvaluationCriteria::route('/{record}/edit'),
        ];
    }
}
