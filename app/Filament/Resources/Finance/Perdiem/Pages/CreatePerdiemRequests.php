<?php
namespace App\Filament\Resources\Finance\Perdiem\Pages;
use App\Filament\Resources\Finance\Perdiem\PerdiemRequestResource;
use App\Services\Finance\PayrollGLPostingService;
use Filament\Resources\Pages\CreateRecord;
class CreatePerdiemRequests extends CreateRecord {
    protected static string $resource = PerdiemRequestResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['reference']   = app(PayrollGLPostingService::class)->generatePdrReference(now()->year);
        $data['prepared_by'] = auth()->id();
        $data['status']      = 'draft';
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $data['days_count'] = max(1, \Carbon\Carbon::parse($data['start_date'])->diffInDays(\Carbon\Carbon::parse($data['end_date'])) + 1);
        }
        $data['total_requested'] = (float)($data['daily_rate'] ?? 0) * (int)($data['days_count'] ?? 1);
        return $data;
    }
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
