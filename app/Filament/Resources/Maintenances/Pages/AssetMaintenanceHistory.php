<?php

namespace App\Filament\Resources\Maintenances\Pages;

use App\Filament\Resources\Maintenances\MaintenanceResource;
use App\Models\Asset;
use App\Models\Maintenance;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Action;

class AssetMaintenanceHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = MaintenanceResource::class;

    protected string $view = 'filament.resources.maintenances.pages.asset-maintenance-history';

    public Asset $asset;

    public function mount(int $assetId)
    {
        $this->asset = Asset::findOrFail($assetId);
    }

    public function getTitle(): string
    {
        return "Maintenance History: {$this->asset->name} ({$this->asset->asset_tag})";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_asset')
                ->label('View Asset Details')
                ->icon('heroicon-o-briefcase')
                ->color('gray')
                ->url(fn () => \App\Filament\Resources\Assets\AssetResource::getUrl('view', ['record' => $this->asset])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Maintenance::query()->where('asset_id', $this->asset->id)->latest('start_date'))
            ->columns([
                TextColumn::make('start_date')
                    ->label('Service Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('maintenanceType.name')
                    ->label('Type')
                    ->badge()
                    ->color('success'),
                TextColumn::make('statusRecord.name')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('title')
                    ->label('Job Title / Description'),
                TextColumn::make('cost')
                    ->label('Cost')
                    ->state(function ($record) {
                        return (float) $record->cost * (float) ($record->currency?->exchange_rate ?? 1);
                    })
                    ->money('ETB'),
                TextColumn::make('notes')
                    ->label('Technician Remarks')
                    ->limit(50),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->url(fn ($record) => MaintenanceResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
