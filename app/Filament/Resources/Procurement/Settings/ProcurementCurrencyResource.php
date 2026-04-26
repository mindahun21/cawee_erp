<?php

namespace App\Filament\Resources\Procurement\Settings;

use App\Models\Currency;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use App\Traits\BelongsToModule;

class ProcurementCurrencyResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Currency::class;

    // ── Navigation ────────────────────────────────────────────────────
    // Hidden from the main sidebar; accessed via the 'Settings' NavigationItem
    // that is registered in AdminPanelProvider — exactly like HR Settings.
    protected static bool $shouldRegisterNavigation = false;

    // Skip CurrencyPolicy (belongs to Fundraising module) entirely.
    // Access is controlled exclusively by the canXxx() overrides below,
    // which check for procurement roles.  This is Filament's own safe bypass.
    protected static bool $shouldSkipAuthorization = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Currencies';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Policy bypasses ──────────────────────────────────────────────
    // We do NOT touch the existing CurrencyPolicy (used by Fundraising).
    // Instead we override the resource-level gate checks so that any
    // procurement officer (or super_admin) can manage currencies here.

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

    // ── Form ─────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Currency Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(10)
                        ->placeholder('e.g., USD, EUR, ETB')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state ?? '')))
                        ->unique(Currency::class, 'code', ignoreRecord: true),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('e.g., US Dollar, Ethiopian Birr'),

                    TextInput::make('symbol')
                        ->required()
                        ->maxLength(10)
                        ->placeholder('e.g., $, €, Br')
                        ->live(onBlur: true),

                    Toggle::make('is_procurement_default')
                        ->label('Set as Default Procurement Currency')
                        ->helperText('When enabled, this currency is pre-selected on all new procurement documents.')
                        ->inline(false),

                    Placeholder::make('preview')
                        ->label('Preview')
                        ->columnSpanFull()
                        ->content(function (Get $get) {
                            $symbol = $get('symbol') ?: 'Br';
                            $code   = strtoupper($get('code') ?: 'ETB');
                            $name   = $get('name') ?: 'Ethiopian Birr';

                            return new HtmlString("
                                <div class='flex flex-col items-center justify-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>
                                    <div class='text-4xl font-bold text-primary-600 dark:text-primary-400 mb-2'>
                                        <span>{$symbol}</span>
                                        <span class='ml-2 font-mono text-2xl bg-primary-100 dark:bg-primary-900 px-2 py-1 rounded'>{$code}</span>
                                    </div>
                                    <div class='text-gray-500 dark:text-gray-400'>{$name}</div>
                                    <div class='mt-2 text-xs text-gray-400'>This is how the currency will appear across procurement documents</div>
                                </div>
                            ");
                        }),
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

                TextColumn::make('symbol')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary'),

                IconColumn::make('is_procurement_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->recordActions([
                // One-click "Set as Default" action
                Action::make('set_default')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Currency $record) => ! $record->is_procurement_default)
                    ->requiresConfirmation()
                    ->modalHeading('Set Default Currency')
                    ->modalDescription('This currency will be pre-selected on all new procurement documents.')
                    ->action(function (Currency $record) {
                        Currency::query()->update(['is_procurement_default' => false]);
                        $record->update(['is_procurement_default' => true]);
                        Notification::make()
                            ->title("{$record->code} is now the default procurement currency")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProcurementCurrencies::route('/'),
        ];
    }
}
