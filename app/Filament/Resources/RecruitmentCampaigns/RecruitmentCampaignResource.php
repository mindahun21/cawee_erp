<?php

namespace App\Filament\Resources\RecruitmentCampaigns;

use App\Filament\Resources\RecruitmentCampaigns\Pages\CreateRecruitmentCampaign;
use App\Filament\Resources\RecruitmentCampaigns\Pages\EditRecruitmentCampaign;
use App\Filament\Resources\RecruitmentCampaigns\Pages\ListRecruitmentCampaigns;
use App\Filament\Resources\RecruitmentCampaigns\Schemas\RecruitmentCampaignForm;
use App\Filament\Resources\RecruitmentCampaigns\Tables\RecruitmentCampaignsTable;
use App\Models\RecruitmentCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecruitmentCampaignResource extends Resource
{
    protected static ?string $model = RecruitmentCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';


    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?string $recordTitleAttribute = 'campaign_name';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentCampaignsTable::configure($table);
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
            'index' => ListRecruitmentCampaigns::route('/'),
            'create' => CreateRecruitmentCampaign::route('/create'),
            'edit' => EditRecruitmentCampaign::route('/{record}/edit'),
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
