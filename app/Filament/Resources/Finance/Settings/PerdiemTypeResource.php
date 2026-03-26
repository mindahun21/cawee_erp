<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Currency;
use App\Models\Finance\PerdiemType;
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

class PerdiemTypeResource extends Resource
{
    protected static ?string $model = PerdiemType::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;
    

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationParentItem = 'Finance Settings';

    protected static ?string $navigationLabel = 'Per Diem Types';

    protected static ?int $navigationSort = 4;

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
            Section::make('Per Diem Type Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(30)
                        ->placeholder('e.g., TRAVEL, TRAINING')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state ?? '')))
                        ->unique(PerdiemType::class, 'code', ignoreRecord: true),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('e.g., Travel Per Diem'),

                    Select::make('category')
                        ->label('Category')
                        ->options(PerdiemType::categories())
                        ->required()
                        ->native(false),

                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(fn () => Currency::orderBy('code')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => Currency::where('code', 'ETB')->value('id')),

                    TextInput::make('default_daily_rate')
                        ->label('Default Daily Rate')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('Used as a starting value; individual requests may override this.'),
                ]),

            Section::make('Options')
                ->columns(2)
                ->schema([
                    Toggle::make('taxable')
                        ->label('Taxable')
                        ->default(false)
                        ->inline(false)
                        ->helperText('Enable to apply per diem tax rules automatically.'),

                    Toggle::make('requires_advance')
                        ->label('Requires Advance Payment')
                        ->default(false)
                        ->inline(false)
                        ->helperText('Request will prompt finance to issue an advance before travel.'),

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
                    ->formatStateUsing(fn ($state) => PerdiemType::categories()[$state] ?? $state)
                    ->badge()
                    ->color('gray'),

                TextColumn::make('default_daily_rate')
                    ->label('Daily Rate')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->prefix(fn ($record) => $record->currency?->symbol . ' '),

                IconColumn::make('taxable')
                    ->label('Taxable')
                    ->boolean()
                    ->trueIcon('heroicon-o-receipt-percent')
                    ->falseIcon('heroicon-o-receipt-percent')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                IconColumn::make('requires_advance')
                    ->label('Advance')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-up-circle')
                    ->falseIcon('heroicon-o-arrow-up-circle')
                    ->trueColor('info')
                    ->falseColor('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(PerdiemType::categories()),
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
            'index' => Pages\ManagePerdiemTypes::route('/'),
        ];
    }
}
