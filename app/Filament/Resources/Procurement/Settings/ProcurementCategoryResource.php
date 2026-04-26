<?php

namespace App\Filament\Resources\Procurement\Settings;

use App\Models\Procurement\ProcurementCategory;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class ProcurementCategoryResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = ProcurementCategory::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = 2;

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
            Section::make('Category Details')
                ->columns(1)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('e.g., Goods, Services, IT Equipment')
                        ->unique(ProcurementCategory::class, 'name', ignoreRecord: true),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive categories will not appear in dropdowns for new documents.'),
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
            'index' => Pages\ManageProcurementCategories::route('/'),
        ];
    }
}
