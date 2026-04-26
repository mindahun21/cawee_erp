<?php

namespace App\Filament\Resources\Procurement\Settings;

use App\Models\Procurement\ProcurementUnit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class ProcurementUnitResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = ProcurementUnit::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Units of Measurement';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }

    public static function canDelete($record): bool
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Unit Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('e.g., Kilograms, Pieces, Boxes')
                        ->unique(ProcurementUnit::class, 'name', ignoreRecord: true),

                    TextInput::make('abbreviation')
                        ->maxLength(20)
                        ->placeholder('e.g., kg, pcs, box')
                        ->unique(ProcurementUnit::class, 'abbreviation', ignoreRecord: true),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull()
                        ->helperText('Inactive units will not appear in dropdowns for new documents.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('abbreviation')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProcurementUnits::route('/'),
        ];
    }
}
