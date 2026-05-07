<?php

namespace App\Livewire;

use App\Models\Asset;
use App\Models\Maintenance;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class AssetMaintenanceHistoryTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public int $assetId;

    public function mount(int $assetId)
    {
        $this->assetId = $assetId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Maintenance::query()->where('asset_id', $this->assetId)->latest('start_date'))
            ->columns([
                TextColumn::make('start_date')
                    ->label('Date')
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
                    ->label('Title/Job'),
                TextColumn::make('cost')
                    ->label('Cost')
                    ->money('ETB'),
                TextColumn::make('notes')
                    ->label('Remarks')
                    ->limit(50),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            {{ $this->table }}
        </div>
        HTML;
    }
}
