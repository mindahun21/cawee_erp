<?php
namespace App\Filament\Resources\Finance\InventoryTakingSheets\Pages;
use App\Filament\Resources\Finance\InventoryTakingSheets\InventoryTakingSheetResource;
use Filament\Resources\Pages\CreateRecord;
class CreateInventoryTakingSheets extends CreateRecord {
    protected static string $resource = InventoryTakingSheetResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $year = now()->year;
        $last = \App\Models\Finance\InventoryTakingSheet::where('reference','like',"INV-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')->orderBy('reference','desc')->value('reference');
        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        $data['reference'] = sprintf('INV-%d-%04d', $year, $seq);
        $data['conducted_by'] = auth()->id();
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
