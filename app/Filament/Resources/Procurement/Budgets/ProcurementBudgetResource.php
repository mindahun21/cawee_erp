<?php

namespace App\Filament\Resources\Procurement\Budgets;

use App\Filament\Resources\Procurement\Budgets\Pages\CreateProcurementBudget;
use App\Filament\Resources\Procurement\Budgets\Pages\EditProcurementBudget;
use App\Filament\Resources\Procurement\Budgets\Pages\ListProcurementBudgets;
use App\Models\Procurement\ProcurementBudget;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProcurementBudgetResource extends Resource
{
    protected static ?string $model = ProcurementBudget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Budget Lines';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Budget Line Details')->columns(2)->schema([
                TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true)
                    ->helperText('Unique budget code, e.g. PROG-2026-IT-001'),
                TextInput::make('title')->required()->maxLength(200),

                TextInput::make('department')->maxLength(150)->nullable(),
                TextInput::make('cost_center')->maxLength(100)->nullable(),
                TextInput::make('fiscal_year')->default(date('Y'))->maxLength(10)->required(),

                Select::make('status')
                    ->options(['Active' => 'Active', 'Exhausted' => 'Exhausted', 'Closed' => 'Closed'])
                    ->default('Active')->required(),

                TextInput::make('allocated_amount')->numeric()->prefix('ETB')->default(0)->required(),
                TextInput::make('committed_amount')->numeric()->prefix('ETB')->default(0)
                    ->helperText('Auto-updated from approved requisitions. Editable for manual corrections.'),
                TextInput::make('expended_amount')->numeric()->prefix('ETB')->default(0)
                    ->helperText('Auto-updated from paid invoices. Editable for adjustments.'),

                Textarea::make('notes')->rows(2)->columnSpanFull()->nullable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable()->weight('semibold')->copyable()->copyMessage('Copied!'),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('department')->toggleable(),
                TextColumn::make('fiscal_year')->badge()->color('gray'),
                TextColumn::make('allocated_amount')->label('Allocated (ETB)')->numeric(2)->prefix('ETB ')->sortable(),
                TextColumn::make('committed_amount')->label('Committed (ETB)')->numeric(2)->prefix('ETB ')->sortable(),
                TextColumn::make('expended_amount')->label('Expended (ETB)')->numeric(2)->prefix('ETB ')->sortable(),
                // Available = allocated - committed - expended (computed)
                TextColumn::make('available_amount')
                    ->label('Available (ETB)')
                    ->numeric(2)
                    ->prefix('ETB ')
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
                    ->weight('semibold'),
                TextColumn::make('utilization_percentage')
                    ->label('Utilization')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        default      => 'success',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Active'    => 'success',
                        'Exhausted' => 'warning',
                        'Closed'    => 'gray',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('code')
            ->filters([
                SelectFilter::make('fiscal_year')
                    ->options(ProcurementBudget::select('fiscal_year')
                        ->distinct()
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->toArray()),
                SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'Exhausted' => 'Exhausted', 'Closed' => 'Closed']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProcurementBudgets::route('/'),
            'create' => CreateProcurementBudget::route('/create'),
            'edit'   => EditProcurementBudget::route('/{record}/edit'),
        ];
    }
}
