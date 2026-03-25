<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\DepreciationLogResource\Pages;
use App\Models\Asset;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DepreciationLogResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Inventory and Asset';
    
    protected static ?int $navigationSort = 70;

    protected static ?string $navigationLabel = 'Depreciations';

    protected static ?string $pluralModelLabel = 'Depreciations';

    protected static ?string $modelLabel = 'Depreciation';
    
    protected static ?string $slug = 'depreciation-logs';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_fixed_asset', true)
            ->whereHas('assetModel.depreciation');
    }

    /**
     * Resolve the Carbon date from the Livewire page's depreciationPeriod property.
     * Falls back to the current month if not set.
     */
    protected static function resolveAsOfDate($livewire): Carbon
    {
        $raw = $livewire->depreciationPeriod ?? null;

        if ($raw) {
            try {
                return Carbon::createFromFormat('Y-m', $raw)->startOfMonth();
            } catch (\Exception $e) {
                // fallback
            }
        }

        return now()->startOfMonth();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable(),
                TextColumn::make('assetModel.depreciation.name')
                    ->label('Depreciation Name')
                    ->sortable(),
                TextColumn::make('assetModel.depreciation.months')
                    ->label('No of Months')
                    ->sortable(),
                TextColumn::make('statusRecord.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Available' => 'success',
                        'Assigned'  => 'info',
                        'Maintenance' => 'warning',
                        'Disposed'  => 'danger',
                        'Lost'      => 'gray',
                        default     => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('purchase_date')
                    ->label('Acquisition Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('eol_date')
                    ->label('EOL Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('purchase_cost')
                    ->label('Cost')
                    ->money('ETB')
                    ->sortable(),
                TextColumn::make('monthly_depreciation_live')
                    ->label('Monthly Depr.')
                    ->money('ETB')
                    ->getStateUsing(fn (Asset $record): float => $record->getMonthlyDepreciationAsOf()),
                TextColumn::make('current_value_live')
                    ->label('Current Value')
                    ->money('ETB')
                    ->getStateUsing(function (Asset $record, TextColumn $column): float {
                        return $record->getCurrentValueAsOf(
                            static::resolveAsOfDate($column->getLivewire())
                        );
                    }),
                TextColumn::make('remaining_value_live')
                    ->label('Remaining Value')
                    ->money('ETB')
                    ->getStateUsing(function (Asset $record, TextColumn $column): float {
                        $currentValue = $record->getCurrentValueAsOf(
                            static::resolveAsOfDate($column->getLivewire())
                        );
                        return (float) $record->purchase_cost - $currentValue;
                    }),
                TextColumn::make('remaining_months_live')
                    ->label('Remaining Months')
                    ->getStateUsing(function (Asset $record, TextColumn $column): int {
                        return $record->getRemainingMonthsAsOf(
                            static::resolveAsOfDate($column->getLivewire())
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('depreciation_month')
                    ->label('Monthly Depreciation')
                    ->form([
                        Forms\Components\DatePicker::make('period')
                            ->label('Month & Year')
                            ->native(true)
                            ->format('Y-m')
                            ->default(now()->format('Y-m'))
                            ->extraInputAttributes(['type' => 'month'])
                            ->live()
                            ->afterStateUpdated(function ($state, $livewire) {
                                if ($state) {
                                    $livewire->depreciationPeriod = $state;
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['period'])) {
                            return $query;
                        }

                        try {
                            $asOf = Carbon::createFromFormat('Y-m', $data['period'])->startOfMonth();
                        } catch (\Exception $e) {
                            return $query;
                        }

                        $asOfStr = $asOf->format('Y-m-d');
                        $endOfMonthStr = $asOf->copy()->endOfMonth()->format('Y-m-d');

                        return $query->where('purchase_date', '<=', $endOfMonthStr)
                            ->whereExists(function ($sub) use ($asOfStr) {
                                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                    ->from('asset_models')
                                    ->join('depreciations', 'asset_models.depreciation_id', '=', 'depreciations.id')
                                    ->whereColumn('asset_models.id', 'assets.asset_model_id')
                                    ->whereRaw("DATE_ADD(assets.purchase_date, INTERVAL depreciations.months MONTH) > ?", [$asOfStr]);
                            });
                    })
                    ->default(['period' => now()->format('Y-m')]),
                Tables\Filters\SelectFilter::make('id')
                    ->label('Asset')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Asset::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => Asset::find($value)?->name),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('assetModel.category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('manufacturer')
                    ->label('Manufacturer')
                    ->relationship('assetModel.manufacturer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('assetType')
                    ->label('Type of Asset')
                    ->relationship('assetModel.type', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('asset_model_id')
                    ->label('Model')
                    ->relationship('assetModel', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('asset_status_id')
                    ->label('Status')
                    ->relationship('statusRecord', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('asset_condition_id')
                    ->label('Condition')
                    ->relationship('conditionRecord', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('acquisition_type_id')
                    ->label('Acquisition Type')
                    ->relationship('acquisitionTypeRecord', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->actions([
                ViewAction::make()
                    ->url(fn (Asset $record): string => \App\Filament\Resources\Assets\AssetResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepreciationLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
