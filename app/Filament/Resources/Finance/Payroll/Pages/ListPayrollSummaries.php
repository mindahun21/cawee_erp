<?php

namespace App\Filament\Resources\Finance\Payroll\Pages;

use App\Filament\Resources\Finance\Payroll\PayrollSummaryResource;
use App\Models\Employee;
use App\Models\Finance\PayrollSummary;
use App\Models\Payroll;
use App\Services\Finance\PayrollGLPostingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListPayrollSummaries extends ListRecords
{
    protected static string $resource = PayrollSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // ── Batch: Generate payroll summaries for a full month ──────────
            Action::make('generate_month')
                ->label('Generate for Month')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->modalWidth('md')
                ->requiresConfirmation(false)
                ->modalHeading('Generate Payroll Summaries for a Month')
                ->modalDescription('This will create Draft Payroll Summaries for every active employee who has an HR Payroll record for the selected month, and who does not yet have a Finance summary for that period.')
                ->form([
                    TextInput::make('payroll_month')
                        ->label('Month (1–12)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->default(now()->month)
                        ->required(),
                    TextInput::make('payroll_year')
                        ->label('Year')
                        ->numeric()
                        ->minValue(2020)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $month  = (int) $data['payroll_month'];
                    $year   = (int) $data['payroll_year'];
                    $svc    = app(PayrollGLPostingService::class);
                    $created = 0;
                    $skipped = 0;
                    $errors  = [];

                    /** @var \Illuminate\Support\Collection $payrolls */
                    $payrolls = Payroll::where('month', $month)
                        ->where('year', $year)
                        ->with('employee')
                        ->get();

                    foreach ($payrolls as $payroll) {
                        /** @var \App\Models\Payroll $payroll */
                        $alreadyExists = PayrollSummary::where('employee_id', $payroll->employee_id)
                            ->where('payroll_month', $month)
                            ->where('payroll_year', $year)
                            ->exists();

                        if ($alreadyExists) {
                            $skipped++;
                            continue;
                        }

                        try {
                            DB::transaction(fn () => $svc->buildSummary($payroll));
                            $created++;
                        } catch (\Throwable $e) {
                            $errors[] = ($payroll->employee?->full_name ?? "ID:{$payroll->id}") . ': ' . $e->getMessage();
                        }
                    }

                    if ($errors) {
                        Notification::make()
                            ->warning()
                            ->title("{$created} created, {$skipped} skipped — " . count($errors) . ' error(s)')
                            ->body(implode("\n", array_slice($errors, 0, 5)))
                            ->send();
                    } else {
                        Notification::make()
                            ->success()
                            ->title("Payroll batch complete: {$created} created, {$skipped} already existed")
                            ->send();
                    }
                }),
        ];
    }
}
