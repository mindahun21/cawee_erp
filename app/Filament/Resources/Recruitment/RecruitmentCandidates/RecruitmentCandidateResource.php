<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCandidates;

use App\Filament\Resources\Recruitment\RecruitmentCandidates\Pages\CreateRecruitmentCandidate;
use App\Filament\Resources\Recruitment\RecruitmentCandidates\Pages\EditRecruitmentCandidate;
use App\Filament\Resources\Recruitment\RecruitmentCandidates\Pages\ListRecruitmentCandidates;
use App\Filament\Resources\Recruitment\RecruitmentCandidates\Schemas\RecruitmentCandidateForm;
use App\Filament\Resources\Recruitment\RecruitmentCandidates\Tables\RecruitmentCandidatesTable;
use App\Models\Recruitment\RecruitmentCandidate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\BelongsToModule;

class RecruitmentCandidateResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = RecruitmentCandidate::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Recruitment';
    protected static ?string $navigationLabel = 'Candidates';
    protected static ?int $navigationSort = 3;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

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
            RelationManagers\ApplicationsRelationManager::class,
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
