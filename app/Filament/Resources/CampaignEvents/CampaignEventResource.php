<?php

namespace App\Filament\Resources\CampaignEvents;

use App\Filament\Resources\CampaignEvents\Pages\CreateCampaignEvent;
use App\Filament\Resources\CampaignEvents\Pages\EditCampaignEvent;
use App\Filament\Resources\CampaignEvents\Pages\ListCampaignEvents;
use App\Filament\Resources\CampaignEvents\Pages\ViewCampaignEvent;
use App\Filament\Resources\CampaignEvents\Schemas\CampaignEventForm;
use App\Filament\Resources\CampaignEvents\Schemas\CampaignEventInfolist;
use App\Filament\Resources\CampaignEvents\Tables\CampaignEventsTable;
use App\Models\CampaignEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignEventResource extends Resource
{
    protected static ?string $model = CampaignEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static string|\UnitEnum|null $navigationGroup = 'Donor Fundraising';

    protected static ?string $recordTitleAttribute = 'event_name';

    public static function form(Schema $schema): Schema
    {
        return CampaignEventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CampaignEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CampaignEventResource\RelationManagers\AttendeesRelationManager::class,
            CampaignEventResource\RelationManagers\VolunteersRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CampaignEventResource\Widgets\EventStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignEvents::route('/'),
            'create' => CreateCampaignEvent::route('/create'),
            'view' => ViewCampaignEvent::route('/{record}'),
            'edit' => EditCampaignEvent::route('/{record}/edit'),
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
