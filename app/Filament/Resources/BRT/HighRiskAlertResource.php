<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\HighRiskAlertResource\Pages\ListHighRiskAlerts;
use App\Models\BRT\BrtProgressUpdate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use App\Traits\BelongsToModule;

class HighRiskAlertResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = BrtProgressUpdate::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'High-Risk Alerts';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        if (!DbSchema::hasTable('brt_progress_updates')) {
            return null;
        }

        return (string) static::getModel()::query()
            ->where('high_risk_flag', true)
            ->whereIn('alert_status', ['open', 'in_review', 'escalated'])
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('high_risk_flag', true)
            ->whereIn('alert_status', ['open', 'in_review', 'escalated']);
    }

    /**
     * We purposefully only provide a table and no forms here.
     * High Risk Alerts are just a filtered view of Progress Updates.
     * Clicking to view/edit will redirect to the Progress Update resource, or we can just make it readonly.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('update_date')
                    ->label('Flagged On')
                    ->date()
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('beneficiary.beneficiary_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('beneficiary.full_name')
                    ->label('Beneficiary')
                    ->searchable(),

                TextColumn::make('summary')
                    ->label('Risk Reason (Summary)')
                    ->wrap()
                    ->limit(80),

                TextColumn::make('author.name')
                    ->label('Flagged By')
                    ->placeholder('—'),

                TextColumn::make('alert_status')
                    ->label('Alert Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('_', ' ', ucwords($state, '_')) : 'Open')
                    ->color(fn (BrtProgressUpdate $record): string => $record->alert_status_color),

                TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->placeholder('—'),
            ])
            ->defaultSort('update_date', 'desc')
            ->filters([
                SelectFilter::make('alert_status')
                    ->options([
                        'open'      => 'Open',
                        'in_review' => 'In Review',
                        'escalated' => 'Escalated',
                    ]),
            ])
            ->recordActions([
                Action::make('mark_in_review')
                    ->label('Mark In Review')
                    ->icon('heroicon-s-arrow-path')
                    ->color('warning')
                    ->visible(fn (BrtProgressUpdate $record): bool => $record->alert_status !== 'in_review')
                    ->action(fn (BrtProgressUpdate $record) => $record->update(['alert_status' => 'in_review'])),
                Action::make('escalate')
                    ->label('Escalate')
                    ->icon('heroicon-s-arrow-trending-up')
                    ->color('primary')
                    ->visible(fn (BrtProgressUpdate $record): bool => $record->alert_status !== 'escalated')
                    ->action(fn (BrtProgressUpdate $record) => $record->update(['alert_status' => 'escalated'])),
                Action::make('resolve_alert')
                    ->label('Resolve Alert')
                    ->icon('heroicon-s-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (BrtProgressUpdate $record) => $record->update([
                        'alert_status' => 'resolved',
                        'resolved_at' => now(),
                    ])),
                Action::make('clear_flag')
                    ->label('Clear Risk Flag')
                    ->icon('heroicon-s-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (BrtProgressUpdate $record) => $record->update(['high_risk_flag' => false])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHighRiskAlerts::route('/'),
        ];
    }
}