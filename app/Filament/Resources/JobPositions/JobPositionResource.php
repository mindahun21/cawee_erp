<?php

namespace App\Filament\Resources\JobPositions;

use App\Filament\Resources\JobPositions\Pages\CreateJobPosition;
use App\Filament\Resources\JobPositions\Pages\EditJobPosition;
use App\Filament\Resources\JobPositions\Pages\ListJobPositions;
use App\Filament\Resources\JobPositions\Schemas\JobPositionForm;
use App\Filament\Resources\JobPositions\Tables\JobPositionsTable;
use App\Models\JobPosition;
use App\Models\RecruitmentPosition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class JobPositionResource extends Resource
{
    protected static ?string $model = RecruitmentPosition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Job Position';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return JobPositionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobPositionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobPositions::route('/'),
            'create' => CreateJobPosition::route('/create'),
            'edit' => EditJobPosition::route('/{record}/edit'),
        ];
    }
}
