<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Donor;
use App\Models\Finance\CostCenter;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CostCenterResource extends Resource
{
    protected static ?string $model = CostCenter::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;
    

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationParentItem = 'Finance Settings';

    protected static ?string $navigationLabel = 'Cost Centers';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Policy bypasses ───────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool  { return static::canViewAny(); }
    public static function canEdit($r): bool  { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cost Center Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(30)
                        ->placeholder('e.g., HO-001, REG-SRS-002')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state ?? '')))
                        ->unique(CostCenter::class, 'code', ignoreRecord: true),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g., Head Office, Sidama Regional Office'),

                    Select::make('type')
                        ->label('Type')
                        ->options(CostCenter::types())
                        ->required()
                        ->native(false),

                    Select::make('parent_id')
                        ->label('Parent Cost Center')
                        ->options(fn () => CostCenter::query()
                            ->where('is_active', true)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn ($cc) => [$cc->id => "[{$cc->code}] {$cc->name}"])
                            ->toArray()
                        )
                        ->searchable()
                        ->nullable()
                        ->helperText('Leave blank if this is a top-level cost center.'),
                ]),

            Section::make('Linked Records')
                ->columns(2)
                ->description('Optionally link to a project or donor for automatic tagging on transactions.')
                ->schema([
                    Select::make('hr_project_id')
                        ->label('Linked Project')
                        ->relationship('hrProject', 'project_name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('donor_id')
                        ->label('Linked Donor')
                        ->options(fn () => Donor::orderBy('organization_name')
                            ->get()
                            ->mapWithKeys(fn ($d) => [$d->id => $d->full_name])
                            ->toArray()
                        )
                        ->searchable()
                        ->nullable()
                        ->helperText('Use for donor-restricted cost centers.'),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(2)
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('info'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('type')
                    ->formatStateUsing(fn ($state) => CostCenter::types()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'head_office'      => 'primary',
                        'regional_office'  => 'info',
                        'project'          => 'success',
                        'donor_restricted' => 'warning',
                        'shared_services'  => 'gray',
                        default            => 'gray',
                    }),

                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('donor.organization_name')
                    ->label('Donor')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(CostCenter::types()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCostCenters::route('/'),
        ];
    }
}
