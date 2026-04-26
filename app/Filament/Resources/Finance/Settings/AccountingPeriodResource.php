<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Finance\AccountingPeriod;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class AccountingPeriodResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = AccountingPeriod::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;
    

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationParentItem = 'Finance Settings';

    protected static ?string $navigationLabel = 'Accounting Periods';

    protected static ?int $navigationSort = 7;

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
            Section::make('Period Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(80)
                        ->placeholder('e.g., July 2026 (Hamle 2018 EC)'),

                    TextInput::make('fiscal_year')
                        ->label('Fiscal Year')
                        ->required()
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(2100)
                        ->placeholder('e.g., 2026'),

                    TextInput::make('period_number')
                        ->label('Period Number')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(13)
                        ->helperText('1–12 for regular months; 13 for the Ethiopian 13th month (Pagumé).'),

                    Select::make('status')
                        ->options(AccountingPeriod::statuses())
                        ->required()
                        ->native(false)
                        ->default('open'),

                    DatePicker::make('start_date')
                        ->required()
                        ->native(false),

                    DatePicker::make('end_date')
                        ->required()
                        ->native(false)
                        ->after('start_date'),

                    Textarea::make('notes')
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
                TextColumn::make('fiscal_year')
                    ->label('FY')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('period_number')
                    ->label('Period')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('start_date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date('d M Y'),

                TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => AccountingPeriod::statuses()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'open'   => 'success',
                        'closed' => 'warning',
                        'locked' => 'danger',
                        default  => 'gray',
                    }),

                TextColumn::make('closedBy.name')
                    ->label('Closed By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AccountingPeriod::statuses()),

                SelectFilter::make('fiscal_year')
                    ->label('Fiscal Year')
                    ->options(fn () => AccountingPeriod::query()
                        ->select('fiscal_year')
                        ->distinct()
                        ->orderByDesc('fiscal_year')
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->toArray()
                    ),
            ])
            ->recordActions([
                // Quick status-toggle action — close an open period
                Action::make('close_period')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Close Accounting Period')
                    ->modalDescription('Closing this period will prevent new journal entries from being posted to it. This action can be reversed by an admin.')
                    ->visible(fn (AccountingPeriod $record) => $record->status === 'open')
                    ->action(function (AccountingPeriod $record) {
                        $record->update([
                            'status'    => 'closed',
                            'closed_by' => auth()->id(),
                            'closed_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Period ' . $record->name . ' has been closed.')
                            ->warning()
                            ->send();
                    }),

                // Lock a closed period — fully prevents any re-opening
                Action::make('lock_period')
                    ->label('Lock')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Lock Accounting Period')
                    ->modalDescription('Locking is permanent. No transactions can ever be posted to a locked period. Proceed only after external audit sign-off.')
                    ->visible(fn (AccountingPeriod $record) => $record->status === 'closed'
                        && auth()->user()?->isSuperAdmin())
                    ->action(function (AccountingPeriod $record) {
                        $record->update(['status' => 'locked']);
                        Notification::make()
                            ->title('Period ' . $record->name . ' is now locked.')
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (AccountingPeriod $record) => $record->status === 'open'),
            ])
            ->defaultSort('fiscal_year', 'desc')
            ->bulkActions([DeleteBulkAction::make()]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccountingPeriods::route('/'),
        ];
    }
}
