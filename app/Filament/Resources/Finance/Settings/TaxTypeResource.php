<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Finance\TaxType;
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
use App\Traits\BelongsToModule;

class TaxTypeResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = TaxType::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;
    

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationParentItem = 'Finance Settings';

    protected static ?string $navigationLabel = 'Tax Types';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Policy bypasses ───────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return true;
        }

        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool  { return static::canViewAny(); }
    public static function canEdit($r): bool  { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tax Type Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(30)
                        ->placeholder('e.g., WHT_15, VAT')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state ?? '')))
                        ->unique(TaxType::class, 'code', ignoreRecord: true),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('e.g., Withholding Tax – 15%'),

                    Select::make('category')
                        ->label('Category')
                        ->options(TaxType::categories())
                        ->required()
                        ->native(false),

                    TextInput::make('default_rate')
                        ->label('Default Rate')
                        ->numeric()
                        ->required()
                        ->suffix('%')
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->helperText('Enter as a percentage, e.g., 15 for 15%.')
                        ->dehydrateStateUsing(fn ($state) => (float) $state / 100)
                        ->afterStateHydrated(fn ($state, $set) => $set('default_rate', (float) $state * 100)),
                ]),

            Section::make('Applicability')
                ->columns(2)
                ->description('Control which payee types this tax applies to.')
                ->schema([
                    Toggle::make('applies_to_individuals')
                        ->label('Applies to Individuals')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('applies_to_organizations')
                        ->label('Applies to Organizations')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('is_automatic')
                        ->label('Auto-Calculate on Vouchers')
                        ->default(false)
                        ->columnSpanFull()
                        ->helperText('When enabled, this tax is auto-populated on qualifying payment vouchers.'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(2)
                        ->nullable(),
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

                TextColumn::make('category')
                    ->formatStateUsing(fn ($state) => TaxType::categories()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'withholding_tax' => 'warning',
                        'vat'             => 'info',
                        'income_tax'      => 'danger',
                        'pension'         => 'success',
                        default           => 'gray',
                    }),

                TextColumn::make('default_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2) . '%')
                    ->alignEnd()
                    ->fontFamily('mono'),

                IconColumn::make('is_automatic')
                    ->label('Auto')
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->falseIcon('heroicon-o-bolt-slash')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(TaxType::categories()),
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
            'index' => Pages\ManageTaxTypes::route('/'),
        ];
    }
}
