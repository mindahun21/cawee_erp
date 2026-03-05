<?php

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\AlertsResource\Pages\CreateAlert;
use App\Filament\Resources\ME\AlertsResource\Pages\EditAlert;
use App\Filament\Resources\ME\AlertsResource\Pages\ListAlerts;
use App\Filament\Resources\ME\AlertsResource\Pages\ViewAlert;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeAlert;
use App\Models\ME\MeIndicator;
use App\Models\ME\MeIndicatorReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AlertsResource extends Resource
{
    protected static ?string $model = MeAlert::class;
    
    protected static ?string $modelLabel = 'Alert';
    
    protected static ?string $pluralModelLabel = 'Alerts';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Alerts';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Alert')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('indicator_id')
                            ->label('Indicator')
                            ->required()
                            ->relationship('indicator', 'name')
                            ->getOptionLabelFromRecordUsing(fn (MeIndicator $record): string => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload(),
                        Select::make('report_id')
                            ->label('Report')
                            ->relationship('report', 'id')
                            ->getOptionLabelFromRecordUsing(fn (MeIndicatorReport $record): string => sprintf(
                                '%s (%s to %s)',
                                $record->indicator?->code ?? 'N/A',
                                optional($record->period_start)->toDateString(),
                                optional($record->period_end)->toDateString()
                            ))
                            ->searchable()
                            ->preload(),
                        Select::make('severity')
                            ->required()
                            ->options([
                                'info' => 'Info',
                                'warning' => 'Warning',
                                'critical' => 'Critical',
                            ]),
                        DateTimePicker::make('resolved_at'),
                        TextInput::make('message')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alert')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('indicator.code')
                            ->label('Indicator Code'),
                        TextEntry::make('indicator.name')
                            ->label('Indicator'),
                        TextEntry::make('severity')
                            ->badge(),
                        TextEntry::make('message')
                            ->columnSpanFull(),
                        TextEntry::make('resolved_at')
                            ->dateTime()
                            ->placeholder('Unresolved'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
                MeAuditTrail::section('me_alerts'),
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
                TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('message')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('resolved_at')
                    ->dateTime()
                    ->placeholder('Unresolved')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'critical' => 'Critical',
                    ]),
                TernaryFilter::make('resolved')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('resolved_at'),
                        false: fn ($query) => $query->whereNull('resolved_at'),
                        blank: fn ($query) => $query
                    ),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (MeAlert $record): bool => $record->resolved_at === null)
                    ->action(function (MeAlert $record): void {
                        $record->update(['resolved_at' => now()]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlerts::route('/'),
            'create' => CreateAlert::route('/create'),
            'view' => ViewAlert::route('/{record}'),
            'edit' => EditAlert::route('/{record}/edit'),
        ];
    }
}
