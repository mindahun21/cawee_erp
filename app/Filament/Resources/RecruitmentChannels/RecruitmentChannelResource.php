<?php

namespace App\Filament\Resources\RecruitmentChannels;

use App\Filament\Resources\RecruitmentChannels\Pages\CreateRecruitmentChannel;
use App\Filament\Resources\RecruitmentChannels\Pages\EditRecruitmentChannel;
use App\Filament\Resources\RecruitmentChannels\Pages\ListRecruitmentChannels;
use App\Filament\Resources\RecruitmentChannels\Schemas\RecruitmentChannelForm;
use App\Filament\Resources\RecruitmentChannels\Tables\RecruitmentChannelsTable;
use App\Models\RecruitmentChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecruitmentChannelResource extends Resource
{
    protected static ?string $model = RecruitmentChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';


    protected static ?string $navigationLabel = 'Skills';

    protected static ?string $recordTitleAttribute = 'form_name';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentChannelsTable::configure($table);
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
            'index' => ListRecruitmentChannels::route('/'),
            'create' => CreateRecruitmentChannel::route('/create'),
            'edit' => EditRecruitmentChannel::route('/{record}/edit'),
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
