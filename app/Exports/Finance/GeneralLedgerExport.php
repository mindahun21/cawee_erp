<?php

namespace App\Exports\Finance;

use App\Models\Finance\GeneralLedger;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class GeneralLedgerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?Collection $records = null
    ) {}

    public function collection(): Collection
    {
        $query = GeneralLedger::with([
            'account',
            'journalEntryLine.journalEntry',
            'journalEntryLine.costCenter',
            'journalEntryLine.donor',
            'currency',
            'period',
        ]);

        return $this->records
            ? $query->whereIn('id', $this->records->pluck('id'))->get()
            : $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Account Code',
            'Account Name',
            'Reference',
            'Narration',
            'Debit (DR)',
            'Credit (CR)',
            'Running Balance',
            'Currency',
            'Cost Centre',
            'Donor',
            'Activity Code',
            'Period',
        ];
    }

    public function map($row): array
    {
        return [
            $row->transaction_date?->format('d/m/Y'),
            $row->account?->code,
            $row->account?->name,
            $row->journalEntryLine?->journalEntry?->reference_number ?? '—',
            $row->journalEntryLine?->narration ?? '—',
            (float) $row->debit  > 0 ? number_format((float) $row->debit,  2) : '',
            (float) $row->credit > 0 ? number_format((float) $row->credit, 2) : '',
            number_format((float) $row->running_balance, 2),
            $row->currency?->code ?? 'ETB',
            $row->journalEntryLine?->costCenter?->name ?? '—',
            $row->journalEntryLine?->donor?->organization_name ?? '—',
            $row->journalEntryLine?->activity_code ?? '—',
            $row->period?->name ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
