<?php

namespace App\Filament\Resources\HR\Settings;

use App\Models\HrLeavePolicy;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use BackedEnum;
use UnitEnum;

class LeavePolicyResource extends Resource
{
    protected static ?string $model = HrLeavePolicy::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Leave Policy';

    protected static ?string $slug = 'hr/settings/leave-policy';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('📋 Ethiopian Labour Proclamation Rules')
                ->description('These rules define how annual leave is accrued per the Ethiopian Labour Proclamation. Hire Date determines which era applies.')
                ->schema([
                    DatePicker::make('era_boundary_date')
                        ->label('Policy Era Boundary Date')
                        ->helperText('Default: July 8, 2019 GC (when Proclamation 1156/2019 took effect). Employees hired before this date use the pre-era rules; those hired on or after use post-era rules.')
                        ->required(),

                    TextInput::make('fiscal_year_month_day')
                        ->label('Fiscal Year Reset Date (MM-DD)')
                        ->helperText('Annual leave resets at this month-day each year. Default 07-08 = July 8 (Hamle 1 EC).')
                        ->placeholder('07-08')
                        ->maxLength(5)
                        ->required(),
                ])
                ->columns(2),

            Section::make('🕰️ Pre-Era Rules (before boundary date)')
                ->description('Applies to employees hired BEFORE the era boundary date. Per Proclamation 377/2003.')
                ->schema([
                    TextInput::make('pre_era_base_days')
                        ->label('Base Annual Leave Days')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->helperText('Initial leave days for a new hire. Default: 14 days.')
                        ->required(),

                    TextInput::make('pre_era_accrual_per_year')
                        ->label('Accrual per Completed Service Year (days)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10)
                        ->helperText('Days added to entitlement for each full year of service. Default: 1 day/year.')
                        ->required(),
                ])
                ->columns(2),

            Section::make('✨ Post-Era Rules (boundary date and after)')
                ->description('Applies to employees hired ON OR AFTER the era boundary date. Per Proclamation 1156/2019.')
                ->schema([
                    TextInput::make('post_era_base_days')
                        ->label('Base Annual Leave Days')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->helperText('Initial entitlement. Default: 16 days.')
                        ->required(),

                    TextInput::make('post_era_accrual_every_n_years')
                        ->label('Accrue +1 Day Every N Years')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->helperText('E.g. 2 means: gain +1 day for every 2 complete service years. Default: 2.')
                        ->required(),
                ])
                ->columns(2),

            Section::make('⚙️ System Behaviour')
                ->schema([
                    TextInput::make('fifo_window_years')
                        ->label('FIFO Balance Window (periods)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->helperText('Number of most-recent fiscal periods included in the rolling leave balance. Default: 3. Older periods are still shown but their balance is not carried forward.')
                        ->required(),

                    Toggle::make('skip_sundays')
                        ->label('Skip Sundays when computing leave end-date')
                        ->helperText('If enabled, Sundays do not count as leave days (working-day leave types only).'),

                    Toggle::make('skip_public_holidays')
                        ->label('Skip Public Holidays when computing leave end-date')
                        ->helperText('If enabled, days from the Holidays list do not count as leave days (working-day leave types only).'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pre_era_base_days')->label('Pre-Era Base Days'),
                TextColumn::make('post_era_base_days')->label('Post-Era Base Days'),
                TextColumn::make('post_era_accrual_every_n_years')->label('Post-Era Accrual (N yrs)'),
                TextColumn::make('fifo_window_years')->label('FIFO Window'),
                IconColumn::make('skip_sundays')->boolean()->label('Skip Sundays'),
                IconColumn::make('skip_public_holidays')->boolean()->label('Skip Holidays'),
                TextColumn::make('era_boundary_date')->date()->label('Boundary Date'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\HR\Settings\LeavePolicyResource\Pages\ManageLeavePolicies::route('/'),
        ];
    }
}
