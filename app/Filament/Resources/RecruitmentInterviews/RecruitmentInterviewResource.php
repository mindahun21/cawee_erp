<?php

namespace App\Filament\Resources\RecruitmentInterviews;

use App\Filament\Resources\RecruitmentInterviews\Pages\CreateRecruitmentInterview;
use App\Filament\Resources\RecruitmentInterviews\Pages\EditRecruitmentInterview;
use App\Filament\Resources\RecruitmentInterviews\Pages\ListRecruitmentInterviews;
use App\Filament\Resources\RecruitmentInterviews\Schemas\RecruitmentInterviewForm;
use App\Filament\Resources\RecruitmentInterviews\Tables\RecruitmentInterviewsTable;
use App\Models\RecruitmentInterview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecruitmentInterviewResource extends Resource
{
    protected static ?string $model = RecruitmentInterview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';


    protected static ?string $navigationLabel = 'Interviews';

    protected static ?string $recordTitleAttribute = 'schedule_name';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentInterviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentInterviewsTable::configure($table);
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
            'index' => ListRecruitmentInterviews::route('/'),
            'create' => CreateRecruitmentInterview::route('/create'),
            'edit' => EditRecruitmentInterview::route('/{record}/edit'),
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
