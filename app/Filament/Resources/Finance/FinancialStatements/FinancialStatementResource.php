<?php

namespace App\Filament\Resources\Finance\FinancialStatements;

use App\Filament\Resources\Finance\FinancialStatements\Pages\CreateFinancialStatements;
use App\Filament\Resources\Finance\FinancialStatements\Pages\EditFinancialStatements;
use App\Filament\Resources\Finance\FinancialStatements\Pages\ListFinancialStatements;
use App\Filament\Resources\Finance\FinancialStatements\Pages\ViewFinancialStatements;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FinancialStatement;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FinancialStatementResource extends Resource
{
    protected static ?string $model                          = FinancialStatement::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Financial Statements';
    protected static ?int    $navigationSort                = 85;
    protected static ?string $slug                          = 'finance/financial-statements';
    protected static bool $shouldSkipAuthorization          = true;

    public static function canViewAny(): bool  { $u = auth()->user(); return $u && ($u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin()); }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($record): bool   { return $record->status === 'draft' && static::canViewAny(); }
    public static function canDelete($record): bool { return $record->status === 'draft' && static::canViewAny(); }
    public static function canView($record): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Statement Header')->icon('heroicon-o-document-text')->columns(3)->schema([
                TextInput::make('reference')->label('Ref #')->disabled()->dehydrated()->placeholder('Auto-generated'),
                Select::make('statement_type')->label('Statement Type')->required()->native(false)->options(FinancialStatement::types()),
                TextInput::make('title')->label('Title')->required()->maxLength(200),
                Select::make('accounting_period_id')->label('Accounting Period')->native(false)
                    ->options(fn () => AccountingPeriod::orderByDesc('end_date')->get()->mapWithKeys(fn ($p) => [$p->id => $p->name])),
                TextInput::make('fiscal_year')->label('Fiscal Year')->numeric()->required()->default(now()->year),
                DatePicker::make('as_of_date')->label('As Of Date')->required()->default(now()),
            ]),
            Section::make('Reporting Filters')->icon('heroicon-o-funnel')->columns(3)->schema([
                Select::make('donor_id')->label('Donor Filter')->native(false)->nullable()->searchable()
                    ->options(fn () => Donor::orderBy('first_name')->get()->mapWithKeys(fn ($d) => [$d->id => $d->full_name])),
                Select::make('project_id')->label('Project Filter')->native(false)->nullable()->searchable()
                    ->options(fn () => Project::orderBy('project_name')->pluck('project_name', 'id')),
                Select::make('cost_center_id')->label('Cost Center Filter')->native(false)->nullable()
                    ->options(fn () => CostCenter::where('is_active', true)->pluck('name', 'id')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Ref #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('statement_type')->label('Type')
                    ->formatStateUsing(fn ($state) => FinancialStatement::types()[$state] ?? $state)->sortable(),
                TextColumn::make('title')->label('Title')->limit(30)->searchable(),
                TextColumn::make('as_of_date')->label('As Of')->date()->sortable(),
                TextColumn::make('accountingPeriod.name')->label('Period')->placeholder('—'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'finalized' => 'success', 'submitted' => 'info', default => 'gray' }),
            ])
            ->filters([
                SelectFilter::make('statement_type')->options(FinancialStatement::types()),
                SelectFilter::make('status')->options(FinancialStatement::statuses()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => $record->status === 'draft'),
                DeleteAction::make()->visible(fn ($record) => $record->status === 'draft'),

                TblAction::make('finalize')->label('Finalize')->icon('heroicon-o-check-badge')->color('success')->button()
                    ->visible(fn ($record) => $record->status === 'draft' && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->forceFill(['status' => 'finalized', 'approved_by' => auth()->id(), 'approved_at' => now()])->save();
                        Notification::make()->success()->title('Statement finalized.')->send();
                    }),
            ])
            ->defaultSort('as_of_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Statement Header')->icon('heroicon-o-document-chart-bar')->columns(3)->schema([
                TextEntry::make('reference')->label('Ref #')->badge()->color('primary')->fontFamily('mono'),
                TextEntry::make('statement_type')->label('Type')->formatStateUsing(fn ($state) => FinancialStatement::types()[$state] ?? $state),
                TextEntry::make('title')->label('Title'),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'finalized' => 'success', 'submitted' => 'info', default => 'gray' }),
                TextEntry::make('fiscal_year')->label('Fiscal Year'),
                TextEntry::make('as_of_date')->label('As Of Date')->date(),
                TextEntry::make('accountingPeriod.name')->label('Accounting Period')->placeholder('—'),
            ]),
            Section::make('Filters Applied')->icon('heroicon-o-funnel')->columns(3)->schema([
                TextEntry::make('donor.first_name')->label('Donor')->formatStateUsing(fn ($state, $record) => $record->donor?->full_name ?? '—')->placeholder('—'),
                TextEntry::make('project.project_name')->label('Project')->placeholder('—'),
                TextEntry::make('costCenter.name')->label('Cost Center')->placeholder('—'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFinancialStatements::route('/'),
            'create' => CreateFinancialStatements::route('/create'),
            'view'   => ViewFinancialStatements::route('/{record}'),
            'edit'   => EditFinancialStatements::route('/{record}/edit'),
        ];
    }
}
