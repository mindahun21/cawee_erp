<?php

namespace App\Filament\Resources\Finance\Budgets;

use App\Filament\Resources\Finance\Budgets\BudgetCodeResource\Pages;
use App\Models\Finance\BudgetCode;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class BudgetCodeResource extends Resource
{
    protected static ?string $model = BudgetCode::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationParentItem = 'Budgets';
    protected static ?string $navigationLabel = 'Budget Codes';
    protected static ?int $navigationSort = 81;
    protected static ?string $slug = 'finance/budget-codes';
    protected static bool $shouldSkipAuthorization = true;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Budget Code Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->maxLength(80)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper(trim((string) $state))))
                        ->unique(BudgetCode::class, 'code', ignoreRecord: true)
                        ->extraInputAttributes(['class' => 'font-mono']),

                    TextInput::make('cost_category')
                        ->label('Cost Category')
                        ->maxLength(120)
                        ->nullable(),

                    TextInput::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->nullable()
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->fontFamily('mono')
                    ->color('primary'),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(80)
                    ->placeholder('—'),

                TextColumn::make('cost_category')
                    ->label('Cost Category')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgetCodes::route('/'),
            'create' => Pages\CreateBudgetCode::route('/create'),
            'view' => Pages\ViewBudgetCode::route('/{record}'),
            'edit' => Pages\EditBudgetCode::route('/{record}/edit'),
        ];
    }
}
