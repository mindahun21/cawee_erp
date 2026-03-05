<?php

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\IndicatorResource\Pages\CreateIndicator;
use App\Filament\Resources\ME\IndicatorResource\Pages\EditIndicator;
use App\Filament\Resources\ME\IndicatorResource\Pages\ListIndicators;
use App\Filament\Resources\ME\IndicatorResource\Pages\ViewIndicator;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeIndicator;
use App\Models\ME\MeProject;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class IndicatorResource extends Resource
{
    protected static ?string $model = MeIndicator::class;
    
    protected static ?string $modelLabel = 'Indicator';
    
    protected static ?string $pluralModelLabel = 'Indicators';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Indicators';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Indicator Definition')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->getOptionLabelFromRecordUsing(fn (MeProject $record): string => "{$record->project_code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state): void {
                                $set('code', static::previewIndicatorCode(
                                    $state !== null ? (int) $state : null,
                                    (string) ($get('name') ?? '')
                                ));
                            })
                            ->columnSpanFull(),
                        TextInput::make('code')
                            ->required()
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Auto-generated from selected Project + Indicator Name.')
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (callable $set, callable $get, $state): void {
                                $set('code', static::previewIndicatorCode(
                                    $get('project_id') !== null ? (int) $get('project_id') : null,
                                    (string) ($state ?? '')
                                ));
                            })
                            ->maxLength(255),
                        Select::make('framework_type')
                            ->required()
                            ->options([
                                'output' => 'Output',
                                'outcome' => 'Outcome',
                                'impact' => 'Impact',
                            ]),
                        Select::make('frequency')
                            ->required()
                            ->options([
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semiannual' => 'Semiannual',
                                'annual' => 'Annual',
                            ]),
                        TextInput::make('unit')
                            ->maxLength(100),
                        Toggle::make('is_active')
                            ->default(true),
                        Toggle::make('disaggregation_required')
                            ->default(false),
                        TextInput::make('threshold_warning')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(70),
                        TextInput::make('threshold_critical')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Indicator')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('project.project_code')
                            ->label('Project Code')
                            ->placeholder('-'),
                        TextEntry::make('project.name')
                            ->label('Project')
                            ->placeholder('-'),
                        TextEntry::make('code'),
                        TextEntry::make('name'),
                        TextEntry::make('framework_type')
                            ->badge(),
                        TextEntry::make('frequency'),
                        TextEntry::make('unit')
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->boolean(),
                        IconEntry::make('disaggregation_required')
                            ->boolean(),
                        TextEntry::make('threshold_warning')
                            ->numeric(2),
                        TextEntry::make('threshold_critical')
                            ->numeric(2),
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                MeAuditTrail::section('me_indicators'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('project.project_code')
                    ->label('Project Code')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('framework_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'output' => 'info',
                        'outcome' => 'warning',
                        'impact' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('frequency')
                    ->badge(),
                IconColumn::make('disaggregation_required')
                    ->boolean()
                    ->label('Disagg Required'),
                TextColumn::make('reports_count')
                    ->counts('reports')
                    ->label('Reports'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('framework_type')
                    ->options([
                        'output' => 'Output',
                        'outcome' => 'Outcome',
                        'impact' => 'Impact',
                    ]),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            IndicatorResource\RelationManagers\TargetsRelationManager::class,
            IndicatorResource\RelationManagers\ReportsRelationManager::class,
            IndicatorResource\RelationManagers\EnabledDisaggregationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndicators::route('/'),
            'create' => CreateIndicator::route('/create'),
            'view' => ViewIndicator::route('/{record}'),
            'edit' => EditIndicator::route('/{record}/edit'),
        ];
    }

    public static function previewIndicatorCode(?int $projectId, ?string $indicatorName): string
    {
        $projectCode = '';
        if ($projectId !== null) {
            $projectCode = (string) (MeProject::query()->whereKey($projectId)->value('project_code') ?? '');
        }

        $projectPrefix = strtoupper((string) preg_replace('/[^A-Z0-9]+/', '', $projectCode));
        if ($projectPrefix === '') {
            $projectPrefix = 'PRJ';
        }

        $name = trim((string) $indicatorName);
        $nameToken = strtoupper(Str::slug($name !== '' ? $name : 'indicator', '_'));
        if ($nameToken === '') {
            $nameToken = 'INDICATOR';
        }

        return substr($projectPrefix . '_' . $nameToken, 0, 100);
    }

    public static function uniqueIndicatorCode(?int $projectId, ?string $indicatorName, ?int $ignoreId = null): string
    {
        $base = static::previewIndicatorCode($projectId, $indicatorName);
        $candidate = $base;
        $suffix = 2;

        while (MeIndicator::query()
            ->where('code', $candidate)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $suffixToken = '_' . $suffix;
            $candidate = substr($base, 0, max(1, 100 - strlen($suffixToken))) . $suffixToken;
            $suffix++;
        }

        return $candidate;
    }
}
