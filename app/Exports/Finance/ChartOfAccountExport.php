<?php

namespace App\Exports\Finance;

use App\Models\Finance\ChartOfAccount;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class ChartOfAccountExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?Collection $records = null
    ) {}

    public function collection(): Collection
    {
        return $this->records ?? ChartOfAccount::with(['accountType', 'financialStatementCategory', 'parent'])
            ->orderBy('code')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Account Name',
            'Account Type',
            'Classification',
            'Normal Balance',
            'Parent Account',
            'FSC',
            'Control Account',
            'Currency Override',
            'Header Account',
            'Donor Fund Account',
            'Active',
            'Notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            $row->accountType?->name ?? '—',
            ucfirst($row->accountType?->classification ?? '—'),
            ucfirst($row->accountType?->normal_balance ?? '—'),
            $row->parent?->name ?? '—',
            $row->financialStatementCategory?->code ?? '—',
            strtoupper($row->is_control_account ?? 'none'),
            $row->currency?->code ?? 'ETB',
            $row->is_header ? 'Yes' : 'No',
            $row->is_donor_fund_account ? 'Yes' : 'No',
            $row->is_active ? 'Active' : 'Inactive',
            $row->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
