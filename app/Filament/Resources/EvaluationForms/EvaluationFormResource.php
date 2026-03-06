<?php

namespace App\Filament\Resources\EvaluationForms;

use App\Filament\Resources\EvaluationForms\Pages\CreateEvaluationForm;
use App\Filament\Resources\EvaluationForms\Pages\EditEvaluationForm;
use App\Filament\Resources\EvaluationForms\Pages\ListEvaluationForms;
use App\Filament\Resources\EvaluationForms\Schemas\EvaluationFormForm;
use App\Filament\Resources\EvaluationForms\Tables\EvaluationFormsTable;
use App\Models\EvaluationForm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EvaluationFormResource extends Resource
{
    protected static ?string $model = EvaluationForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Evaluation Form';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'form_name';

    public static function form(Schema $schema): Schema
    {
        return EvaluationFormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EvaluationFormsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvaluationForms::route('/'),
            'create' => CreateEvaluationForm::route('/create'),
            'edit' => EditEvaluationForm::route('/{record}/edit'),
        ];
    }
}
