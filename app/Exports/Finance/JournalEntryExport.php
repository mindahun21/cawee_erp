<?php

namespace App\Exports\Finance;

use App\Models\Finance\JournalEntry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class JournalEntryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?Collection $records = null
    ) {}

    public function collection(): Collection
    {
        $query = JournalEntry::with(['lines.account', 'lines.costCenter', 'lines.donor', 'currency', 'preparedBy', 'period']);

        return $this->records
            ? $query->whereIn('id', $this->records->pluck('id'))->get()
            : $query->orderByDesc('transaction_date')->get();
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Date',
            'Period',
            'Status',
            'Source',
            'Currency',
            'Description',
            'Account Code',
            'Account Name',
            'Debit',
            'Credit',
            'Cost Centre',
            'Donor',
            'Activity Code',
            'Narration',
            'Prepared By',
            'Posted At',
        ];
    }

    public function map($je): array
    {
        $rows = [];
        foreach ($je->lines as $line) {
            $rows[] = [
                $je->reference_number,
                $je->transaction_date?->format('d/m/Y'),
                $je->period?->name ?? '—',
                ucfirst(str_replace('_', ' ', $je->status)),
                ucfirst($je->source ?? '—'),
                $je->currency?->code ?? 'ETB',
                $je->description,
                $line->account?->code,
                $line->account?->name,
                number_format((float) $line->debit, 2),
                number_format((float) $line->credit, 2),
                $line->costCenter?->name ?? '—',
                $line->donor?->organization_name ?? '—',
                $line->activity_code ?? '—',
                $line->narration ?? '—',
                $je->preparedBy?->name ?? '—',
                $je->posted_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
