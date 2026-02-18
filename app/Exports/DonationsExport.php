<?php

namespace App\Exports;

use App\Models\Donation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DonationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Donation::with(['donor', 'campaign', 'donationType', 'currency']);

        // Apply filters
        if (!empty($this->filters['ids'])) {
            $query->whereIn('id', $this->filters['ids']);
        }

        if (!empty($this->filters['donor_id'])) {
            $query->where('donor_id', $this->filters['donor_id']);
        }

        if (!empty($this->filters['campaign_id'])) {
            $query->where('campaign_id', $this->filters['campaign_id']);
        }

        if (!empty($this->filters['donation_type_id'])) {
            $query->where('donation_type_id', $this->filters['donation_type_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('donation_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('donation_date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('donation_date', 'desc')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Receipt Number',
            'Donor',
            'Campaign',
            'Type',
            'Amount',
            'Currency',
            'Date',
            'Recurring',
            'Payment Method',
            'Transaction ID',
            'Status',
            'Notes',
        ];
    }

    /**
     * @param Donation $donation
     * @return array
     */
    public function map($donation): array
    {
        return [
            $donation->id,
            $donation->receipt_number,
            $donation->donor->full_name ?? '',
            $donation->campaign?->title ?? 'General',
            $donation->donationType->name ?? '',
            $donation->amount,
            $donation->currency->code ?? '',
            $donation->donation_date->format('Y-m-d'),
            $donation->is_recurring ? 'Yes' : 'No',
            $donation->payment_method ?? '',
            $donation->transaction_id ?? '',
            ucfirst($donation->status),
            $donation->notes ?? '',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
