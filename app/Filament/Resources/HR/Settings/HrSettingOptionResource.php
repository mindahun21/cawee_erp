<?php

namespace App\Filament\Resources\HR\Settings;

use App\Models\HrSettingOption;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class HrSettingOptionResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = HrSettingOption::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Car & Rent Dropdowns';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')
            ->options(HrSettingOption::CATEGORIES)
            ->searchable()
            ->required(),

            TextInput::make('label')
            ->required()
            ->maxLength(150),

            TextInput::make('code')
            ->maxLength(100)
            ->helperText('Optional short code.'),

            TextInput::make('sort_order')
            ->numeric()
            ->default(0)
            ->required(),

            Toggle::make('is_active')
            ->default(true),

            Textarea::make('description')
            ->rows(2)
            ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('category')
            ->formatStateUsing(fn(string $state) => HrSettingOption::CATEGORIES[$state] ?? $state)
            ->badge()
            ->sortable(),
            TextColumn::make('label')->searchable()->sortable()->weight('semibold'),
            TextColumn::make('code')->toggleable(),
            TextColumn::make('sort_order')->sortable(),
            IconColumn::make('is_active')->boolean(),
        ])
            ->filters([
            SelectFilter::make('category')->options(HrSettingOption::CATEGORIES),
        ])
            ->defaultSort('category')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHrSettingOptions::route('/'),
        ];
    }
}
