<?php

namespace App\Console\Commands;

use App\Models\BranchUtility;
use App\Models\OfficeRentAgreement;
use App\Models\User;
use App\Models\UtilityPayment;
use App\Models\VehicleInspection;
use App\Models\VehicleLicense;
use App\Models\VehicleMaintenanceRecord;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GenerateHrCarRentAlerts extends Command
{
    protected $signature = 'hr:car-rent-alerts';

    protected $description = 'Generate HR car, office rent, and utility reminders';

    public function handle(): int
    {
        $recipients = $this->getRecipients();
        if ($recipients->isEmpty()) {
            $this->warn('No HR recipients found. Skipping notifications.');
            return self::SUCCESS;
        }

        $this->sendAgreementExpiryAlerts($recipients);
        $this->sendVehicleLicenseAlerts($recipients);
        $this->sendVehicleInspectionAlerts($recipients);
        $this->sendUtilityAlerts($recipients);
        $this->sendMaintenanceDueAlerts($recipients);

        $this->info('HR car and rent alerts processed.');
        return self::SUCCESS;
    }

    protected function getRecipients(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'hr_director', 'hr_officer', 'hr_supervisor']))
            ->get();
    }

    protected function sendAgreementExpiryAlerts(Collection $recipients): void
    {
        $leadDays = [90, 60, 30];

        foreach ($leadDays as $days) {
            $agreements = OfficeRentAgreement::query()
                ->with('branch')
                ->whereIn('status', ['Active', 'Approved'])
                ->whereDate('end_date', now()->addDays($days)->toDateString())
                ->get();

            foreach ($agreements as $agreement) {
                $key = 'hr_alert_agreement_' . $agreement->id . '_' . $days . '_' . now()->toDateString();
                $this->sendUnique(
                    $key,
                    $recipients,
                    'Office Agreement Expiry',
                    "{$agreement->agreement_code} expires in {$days} days for {$agreement->branch?->branch_name}."
                );
            }
        }
    }

    protected function sendVehicleLicenseAlerts(Collection $recipients): void
    {
        $leadDays = [60, 30, 7];

        foreach ($leadDays as $days) {
            $licenses = VehicleLicense::query()
                ->with('vehicle.vehicleDetail')
                ->whereDate('bolo_expiry_date', now()->addDays($days)->toDateString())
                ->get();

            foreach ($licenses as $license) {
                $plate = $license->vehicle?->plate_number ?? 'Unknown Plate';
                $key = 'hr_alert_bolo_' . $license->id . '_' . $days . '_' . now()->toDateString();
                $this->sendUnique(
                    $key,
                    $recipients,
                    'Vehicle Bolo Renewal',
                    "Bolo for {$plate} expires in {$days} days."
                );
            }
        }
    }

    protected function sendVehicleInspectionAlerts(Collection $recipients): void
    {
        $leadDays = [60, 30, 7];

        foreach ($leadDays as $days) {
            $inspections = VehicleInspection::query()
                ->with('vehicle.vehicleDetail')
                ->whereDate('inspection_expiry_date', now()->addDays($days)->toDateString())
                ->get();

            foreach ($inspections as $inspection) {
                $plate = $inspection->vehicle?->plate_number ?? 'Unknown Plate';
                $key = 'hr_alert_inspection_' . $inspection->id . '_' . $days . '_' . now()->toDateString();
                $this->sendUnique(
                    $key,
                    $recipients,
                    'Vehicle Inspection Expiry',
                    "Inspection for {$plate} expires in {$days} days."
                );
            }
        }
    }

    protected function sendUtilityAlerts(Collection $recipients): void
    {
        $dueUtilities = BranchUtility::query()
            ->with(['utilityType', 'branch'])
            ->where('status', 'Active')
            ->whereDate('next_due_date', '<=', now()->addDays(7)->toDateString())
            ->get();

        foreach ($dueUtilities as $utility) {
            $utilityName = $utility->utilityType?->label ?? 'Utility';
            $branch = $utility->branch?->branch_name ?? 'Unknown Branch';
            $key = 'hr_alert_utility_due_' . $utility->id . '_' . now()->toDateString();
            $this->sendUnique(
                $key,
                $recipients,
                'Utility Bill Due',
                "{$utilityName} bill is due soon for {$branch}."
            );
        }

        $overdues = UtilityPayment::query()
            ->with(['utility.branch', 'utility.utilityType'])
            ->where('status', 'Pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($overdues as $payment) {
            $payment->update(['status' => 'Overdue']);

            $branch = $payment->utility?->branch?->branch_name ?? 'Unknown Branch';
            $utilityName = $payment->utility?->utilityType?->label ?? 'Utility';
            $key = 'hr_alert_utility_overdue_' . $payment->id . '_' . now()->toDateString();
            $this->sendUnique(
                $key,
                $recipients,
                'Utility Payment Overdue',
                "{$utilityName} payment is overdue for {$branch}."
            );
        }
    }

    protected function sendMaintenanceDueAlerts(Collection $recipients): void
    {
        $records = VehicleMaintenanceRecord::query()
            ->with('vehicle.vehicleDetail')
            ->whereDate('next_service_date', '<=', now()->addDays(7)->toDateString())
            ->get();

        foreach ($records as $record) {
            $plate = $record->vehicle?->plate_number ?? 'Unknown Plate';
            $key = 'hr_alert_maintenance_' . $record->id . '_' . now()->toDateString();
            $this->sendUnique(
                $key,
                $recipients,
                'Vehicle Service Due',
                "Scheduled service is due soon for {$plate}."
            );
        }
    }

    protected function sendUnique(string $cacheKey, Collection $recipients, string $title, string $body): void
    {
        if (! Cache::add($cacheKey, true, now()->addDay())) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->sendToDatabase($recipients);
    }
}
