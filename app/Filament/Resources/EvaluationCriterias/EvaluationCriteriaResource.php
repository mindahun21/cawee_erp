<?php

namespace App\Filament\Resources\EvaluationCriterias;

use App\Filament\Resources\EvaluationCriteriaResource\RelationManagers\ScoresRelationManager;
use App\Filament\Resources\EvaluationCriterias\Pages\CreateEvaluationCriteria;
use App\Filament\Resources\EvaluationCriterias\Pages\EditEvaluationCriteria;
use App\Filament\Resources\EvaluationCriterias\Pages\ListEvaluationCriterias;
use App\Filament\Resources\EvaluationCriterias\RelationManagers\ScoresRelationManager as RelationManagersScoresRelationManager;
use App\Filament\Resources\EvaluationCriterias\Schemas\EvaluationCriteriaForm;
use App\Filament\Resources\EvaluationCriterias\Tables\EvaluationCriteriasTable;
use App\Models\EvaluationCriteria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EvaluationCriteriaResource extends Resource
{
    protected static ?string $model = EvaluationCriteria::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Evaluation Criteria';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'criteria_name';

    public static function form(Schema $schema): Schema
    {
        return EvaluationCriteriaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EvaluationCriteriasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagersScoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvaluationCriterias::route('/'),
            'create' => CreateEvaluationCriteria::route('/create'),
            'edit' => EditEvaluationCriteria::route('/{record}/edit'),
        ];
    }
}
