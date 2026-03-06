<?php

namespace App\Filament\Resources\RecruitmentCandidates;

use App\Filament\Resources\RecruitmentCandidates\Pages\CreateRecruitmentCandidate;
use App\Filament\Resources\RecruitmentCandidates\Pages\EditRecruitmentCandidate;
use App\Filament\Resources\RecruitmentCandidates\Pages\ListRecruitmentCandidates;
use App\Filament\Resources\RecruitmentCandidates\Schemas\RecruitmentCandidateForm;
use App\Filament\Resources\RecruitmentCandidates\Tables\RecruitmentCandidatesTable;
use App\Models\RecruitmentCandidate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecruitmentCandidateResource extends Resource
{
    protected static ?string $model = RecruitmentCandidate::class;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationLabel = 'Candidates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 1;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'candidate_code';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentCandidateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentCandidatesTable::configure($table);
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
            'index' => ListRecruitmentCandidates::route('/'),
            'create' => CreateRecruitmentCandidate::route('/create'),
            'edit' => EditRecruitmentCandidate::route('/{record}/edit'),
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
