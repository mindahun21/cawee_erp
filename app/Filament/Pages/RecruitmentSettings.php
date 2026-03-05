<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RecruitmentSettings extends Page
{
    protected string $view = 'filament.pages.recruitment-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
}
