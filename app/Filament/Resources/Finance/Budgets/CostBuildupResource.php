<?php

namespace App\Filament\Resources\Finance\Budgets;

use App\Models\Finance\CostBuildup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CostBuildupResource extends Resource
{
    protected static ?string $model = CostBuildup::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationParentItem = 'Budgets';
    protected static ?string $navigationLabel = 'Cost Buildups';
    protected static ?string $slug = 'finance/budgets/cost-buildups';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool { return true; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cost Buildup Details')->columns(2)->schema([
                TextInput::make('reference')->disabled()->dehydrated()->placeholder('Auto-generated'),
                DatePicker::make('transaction_date')->required()->default(now()),
                Select::make('budget_id')->relationship('budget', 'name')->searchable()->native(false)->required(),
                Select::make('budget_line_id')->relationship('budgetLine', 'activity_code')->searchable()->native(false)->required(),
                Select::make('account_id')->relationship('account', 'name')->searchable()->native(false)->required(),
                Select::make('currency_id')->relationship('currency', 'code')->searchable()->native(false)->required(),
                TextInput::make('amount')->numeric()->required(),
                TextInput::make('exchange_rate_to_base')->numeric()->default(1.000000)->required(),
                TextInput::make('activity_code')->nullable(),
                Select::make('project_id')->relationship('project', 'project_name')->searchable()->native(false)->nullable(),
                Select::make('cost_center_id')->relationship('costCenter', 'name')->searchable()->native(false)->nullable(),
                Select::make('donor_id')->relationship('donor', 'first_name')->searchable()->native(false)->nullable(),
                Textarea::make('description')->columnSpanFull()->nullable(),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->sortable(),
                TextColumn::make('transaction_date')->date()->sortable(),
                TextColumn::make('budget.name')->searchable(),
                TextColumn::make('account.name')->searchable()->limit(20),
                TextColumn::make('amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('currency.code')->badge(),
                TextColumn::make('activity_code')->searchable(),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Finance\Budgets\CostBuildupResource\Pages\ManageCostBuildups::route('/'),
        ];
    }
}
