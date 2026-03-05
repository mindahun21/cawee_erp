<?php

namespace App\Filament\Resources\OtherSettings;

use App\Filament\Resources\OtherSettings\Schemas\OtherSettingsForm;
use App\Models\OtherSettings;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use BackedEnum;

class OtherSettingsResource extends Resource
{
    protected static ?string $model = OtherSettings::class;

    // Correct types for Filament v3
    protected static UnitEnum|string|null $navigationGroup = 'Recruitment';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationParentItem = 'Settings';
    protected static ?string $navigationLabel = 'Other Settings';
    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'other';

    public static function form(Schema $schema): Schema
    {
        return OtherSettingsForm::configure($schema);
    }

    // Singleton: only edit page
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOtherSettings::route('/'),
            'edit' => Pages\EditOtherSettings::route('/{record}/edit'),
        ];
    }

    // Make sidebar link point to the singleton row
    public static function getNavigationUrl(): string
    {
        $settings = OtherSettings::first() ?? OtherSettings::create([]);
        return static::getUrl('edit', ['record' => $settings->id]);
    }

    // Force Filament to render this resource in the sidebar
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
