<?php

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\ReportResource\Pages\CreateReport;
use App\Filament\Resources\ME\ReportResource\Pages\EditReport;
use App\Filament\Resources\ME\ReportResource\Pages\ListReports;
use App\Filament\Resources\ME\ReportResource\Pages\ViewReport;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeDisaggregationCategory;
use App\Models\ME\MeDisaggregationOption;
use App\Models\ME\MeIndicator;
use App\Models\ME\MeIndicatorReport;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = MeIndicatorReport::class;
    
    protected static ?string $modelLabel = 'Report';
    
    protected static ?string $pluralModelLabel = 'Reports';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Periodic Reporting')
                    ->columns(2)
                    ->schema([
                        Select::make('indicator_id')
                            ->label('Indicator')
                            ->relationship('indicator', 'name')
                            ->getOptionLabelFromRecordUsing(fn (MeIndicator $record): string => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        DatePicker::make('period_start')
                            ->required(),
                        DatePicker::make('period_end')
                            ->required()
                            ->afterOrEqual('period_start'),
                        TextInput::make('actual_value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                    $indicatorId = $get('indicator_id');

                                    if (! $indicatorId) {
                                        return;
                                    }

                                    $indicator = MeIndicator::query()->find($indicatorId);

                                    if (! $indicator || ! $indicator->disaggregation_required) {
                                        return;
                                    }

                                    $rows = collect($get('disaggregationValues') ?? []);
                                    $sum = (float) $rows->sum(fn (array $row): float => (float) ($row['value'] ?? 0));

                                    if (abs($sum - (float) $value) > 0.01) {
                                        $fail('Disaggregation sum must equal actual value (tolerance 0.01) when disaggregation is required.');
                                    }
                                },
                            ]),
                        TextInput::make('scope_location')
                            ->maxLength(255),
                        TextInput::make('scope_project')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                \Filament\Schemas\Components\Section::make('Disaggregation Values')
                    ->schema([
                        Repeater::make('disaggregationValues')
                            ->relationship('disaggregationValues')
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Disaggregation Value')
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->required()
                                    ->options(function (Get $get): array {
                                        $indicatorId = $get('../../indicator_id');

                                        if (! $indicatorId) {
                                            return MeDisaggregationCategory::query()
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        }

                                        return MeDisaggregationCategory::query()
                                            ->whereHas('indicators', fn ($query) => $query->where('me_indicators.id', $indicatorId))
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->live(),
                                Select::make('option_id')
                                    ->label('Option')
                                    ->required()
                                    ->options(fn (Get $get): array => MeDisaggregationOption::query()
                                        ->when($get('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
                                        ->orderBy('sort_order')
                                        ->pluck('label', 'id')
                                        ->toArray())
                                    ->searchable(),
                                TextInput::make('value')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('indicator.code')
                            ->label('Indicator Code'),
                        TextEntry::make('indicator.name')
                            ->label('Indicator'),
                        TextEntry::make('period_start')
                            ->date(),
                        TextEntry::make('period_end')
                            ->date(),
                        TextEntry::make('actual_value')
                            ->numeric(2),
                        TextEntry::make('resolved_target')
                            ->label('Target')
                            ->state(fn (MeIndicatorReport $record): float => $record->resolvedTargetValue())
                            ->numeric(2),
                        TextEntry::make('progress_percent')
                            ->label('Progress')
                            ->state(fn (MeIndicatorReport $record): string => number_format($record->progressPercent(), 2) . '%')
                            ->badge(),
                        TextEntry::make('status')
                            ->state(fn (MeIndicatorReport $record): string => str_replace('_', ' ', $record->progressStatus()))
                            ->badge(),
                        TextEntry::make('scope_location')
                            ->placeholder('-'),
                        TextEntry::make('scope_project')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('disaggregation_summary')
                            ->label('Disaggregation')
                            ->state(fn (MeIndicatorReport $record): string => static::renderDisaggregation($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                MeAuditTrail::section('me_indicator_reports'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('indicator.code')
                    ->label('Code')
                    ->badge()
                    ->sortable(),
                TextColumn::make('indicator.name')
                    ->label('Indicator')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('indicator.framework_type')
                    ->label('Framework')
                    ->badge(),
                TextColumn::make('resolved_target')
                    ->label('Latest Target')
                    ->state(fn (MeIndicatorReport $record): float => $record->resolvedTargetValue())
                    ->numeric(2),
                TextColumn::make('actual_value')
                    ->label('Latest Actual')
                    ->numeric(2),
                TextColumn::make('progress_percent')
                    ->label('Progress %')
                    ->state(fn (MeIndicatorReport $record): string => number_format($record->progressPercent(), 2) . '%')
                    ->badge()
                    ->color(fn (MeIndicatorReport $record): string => match ($record->progressStatus()) {
                        'on_track' => 'success',
                        'needs_attention' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('status')
                    ->state(fn (MeIndicatorReport $record): string => str_replace('_', ' ', $record->progressStatus()))
                    ->badge()
                    ->color(fn (MeIndicatorReport $record): string => match ($record->progressStatus()) {
                        'on_track' => 'success',
                        'needs_attention' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('period_end')
                    ->label('Last Reported')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('framework_type')
                    ->label('Framework')
                    ->options([
                        'output' => 'Output',
                        'outcome' => 'Outcome',
                        'impact' => 'Impact',
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($innerQuery, $value) => $innerQuery->whereHas('indicator', fn ($indicatorQuery) => $indicatorQuery->where('framework_type', $value))
                    )),
                SelectFilter::make('scope_location')
                    ->options(fn (): array => MeIndicatorReport::query()
                        ->whereNotNull('scope_location')
                        ->distinct()
                        ->orderBy('scope_location')
                        ->pluck('scope_location', 'scope_location')
                        ->toArray()),
                Filter::make('period')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('to'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($innerQuery, $value) => $innerQuery->whereDate('period_end', '>=', $value))
                            ->when($data['to'] ?? null, fn ($innerQuery, $value) => $innerQuery->whereDate('period_start', '<=', $value));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'view' => ViewReport::route('/{record}'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }

    private static function renderDisaggregation(MeIndicatorReport $record): string
    {
        $values = $record->disaggregationValues()->with(['category', 'option'])->get();

        if ($values->isEmpty()) {
            return 'No disaggregation values.';
        }

        $rows = $values
            ->map(function ($value): string {
                return sprintf(
                    '<tr><td style="padding:6px 8px;">%s</td><td style="padding:6px 8px;">%s</td><td style="padding:6px 8px;">%s</td></tr>',
                    e($value->category?->name ?? '-'),
                    e($value->option?->label ?? '-'),
                    e(number_format((float) $value->value, 2))
                );
            })
            ->implode('');

        return sprintf(
            '<table style="width:100%%;border-collapse:collapse;"><thead><tr><th style="text-align:left;padding:6px 8px;">Category</th><th style="text-align:left;padding:6px 8px;">Option</th><th style="text-align:left;padding:6px 8px;">Value</th></tr></thead><tbody>%s</tbody></table>',
            $rows
        );
    }
}
