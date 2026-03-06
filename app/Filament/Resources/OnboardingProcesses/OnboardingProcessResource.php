<?php

namespace App\Filament\Resources\OnboardingProcesses;

use App\Filament\Resources\OnboardingProcesses\Pages\CreateOnboardingProcess;
use App\Filament\Resources\OnboardingProcesses\Pages\EditOnboardingProcess;
use App\Filament\Resources\OnboardingProcesses\Pages\ListOnboardingProcesses;
use App\Filament\Resources\OnboardingProcesses\Schemas\OnboardingProcessForm;
use App\Filament\Resources\OnboardingProcesses\Tables\OnboardingProcessesTable;
use App\Models\OnboardingProcess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OnboardingProcessResource extends Resource
{
    protected static ?string $model = OnboardingProcess::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'On-Boarding Process';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'order';

    public static function form(Schema $schema): Schema
    {
        return OnboardingProcessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnboardingProcessesTable::configure($table);
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
            'index' => ListOnboardingProcesses::route('/'),
            'create' => CreateOnboardingProcess::route('/create'),
            'edit' => EditOnboardingProcess::route('/{record}/edit'),
        ];
    }
}
