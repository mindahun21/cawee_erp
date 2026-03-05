<?php

namespace App\Filament\Resources\RecruitmentPositions;

use App\Filament\Resources\RecruitmentPositions\Pages\CreateRecruitmentPosition;
use App\Filament\Resources\RecruitmentPositions\Pages\EditRecruitmentPosition;
use App\Filament\Resources\RecruitmentPositions\Pages\ListRecruitmentPositions;
use App\Filament\Resources\RecruitmentPositions\Schemas\RecruitmentPositionForm;
use App\Filament\Resources\RecruitmentPositions\Tables\RecruitmentPositionsTable;
use App\Models\RecruitmentPosition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecruitmentPositionResource extends Resource
{
    protected static ?string $model = RecruitmentPosition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentPositionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentPositionsTable::configure($table);
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
            // 'index' => ListRecruitmentPositions::route('/'),
            'create' => CreateRecruitmentPosition::route('/create'),
            'edit' => EditRecruitmentPosition::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
