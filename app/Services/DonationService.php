<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\DonationReceipt;

class DonationService
{
    /**
     * Process a new donation with validation and side effects
     */
    public function processDonation(array $data): array
    {
        try {
            DB::beginTransaction();

            // Create the donation
            $donation = Donation::create($data);

            // Update donor statistics
            $this->updateDonorStatistics($donation->donor_id);

            // Update campaign statistics if applicable
            if ($donation->campaign_id) {
                $this->updateCampaignStatistics($donation->campaign_id);
            }

            DB::commit();

            return [
                'success' => true,
                'donation' => $donation,
                'receipt_number' => $donation->receipt_number,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate receipt data for a donation
     */
    public function generateReceipt(int $donationId): array
    {
        $donation = Donation::with(['donor', 'campaign', 'donationType', 'currency'])
            ->findOrFail($donationId);

        return [
            'receipt_number' => $donation->receipt_number,
            'donation_id' => $donation->id,
            'date_issued' => now()->format('Y-m-d H:i:s'),
            'donor_name' => $donation->donor->full_name,
            'donor_email' => $donation->donor->email,
            'donor_address' => $this->formatDonorAddress($donation->donor),
            'amount' => $donation->amount,
            'currency' => $donation->currency->code,
            'currency_symbol' => $donation->currency->symbol,
            'donation_type' => $donation->donationType->name,
            'campaign' => $donation->campaign?->title ?? 'General Donation',
            'donation_date' => $donation->donation_date->format('Y-m-d'),
            'is_tax_deductible' => $donation->donationType->tax_deductible,
            'is_recurring' => $donation->is_recurring,
            'payment_method' => $donation->payment_method,
            'transaction_id' => $donation->transaction_id,
            'notes' => $donation->notes,
        ];
    }

    /**
     * Format donor address for receipt
     */
    private function formatDonorAddress(Donor $donor): string
    {
        $parts = array_filter([
            $donor->address,
            $donor->city,
            $donor->state,
            $donor->postal_code,
            $donor->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Update donor statistics
     */
    public function updateDonorStatistics(int $donorId): void
    {
        $stats = Donation::where('donor_id', $donorId)
            ->where('status', 'completed')
            ->selectRaw('
                COUNT(*) as total_donations,
                SUM(amount) as total_amount,
                MAX(donation_date) as last_donation_date,
                AVG(amount) as average_donation
            ')
            ->first();

        // You can store these in a donor_statistics table or cache
        // For now, we'll just calculate them on demand
    }

    /**
     * Update campaign statistics
     */
    public function updateCampaignStatistics(int $campaignId): void
    {
        $stats = Donation::where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->selectRaw('
                SUM(amount) as total_raised,
                COUNT(DISTINCT donor_id) as donor_count,
                COUNT(*) as donation_count
            ')
            ->first();

        Campaign::where('id', $campaignId)->update([
            'total_raised' => $stats->total_raised ?? 0,
            'donor_count' => $stats->donor_count ?? 0,
        ]);
    }

    /**
     * Get donation statistics with optional filters
     */
    public function getDonationStatistics(array $filters = []): array
    {
        $query = Donation::query()->where('status', 'completed');

        // Apply filters
        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (!empty($filters['donor_id'])) {
            $query->where('donor_id', $filters['donor_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('donation_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('donation_date', '<=', $filters['date_to']);
        }

        // Get overall statistics
        $overall = $query->selectRaw('
            COUNT(*) as total_count,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount,
            MAX(amount) as largest_donation,
            MIN(amount) as smallest_donation
        ')->first();

        // Get recurring statistics
        $recurring = (clone $query)->where('is_recurring', true)
            ->selectRaw('COUNT(*) as count, SUM(amount) as total')
            ->first();

        // Get monthly trends (last 12 months)
        $monthlyTrends = $this->getMonthlyTrends($filters);

        // Get donation type distribution
        $typeDistribution = $this->getDonationTypeDistribution($filters);

        // Get top donors
        $topDonors = $this->getTopDonors(5, $filters);

        return [
            'overall' => [
                'total_donations' => $overall->total_count ?? 0,
                'total_amount' => $overall->total_amount ?? 0,
                'average_amount' => $overall->average_amount ?? 0,
                'largest_donation' => $overall->largest_donation ?? 0,
                'smallest_donation' => $overall->smallest_donation ?? 0,
            ],
            'recurring' => [
                'count' => $recurring->count ?? 0,
                'total_amount' => $recurring->total ?? 0,
            ],
            'monthly_trends' => $monthlyTrends,
            'type_distribution' => $typeDistribution,
            'top_donors' => $topDonors,
        ];
    }

    /**
     * Get monthly donation trends
     */
    public function getMonthlyTrends(array $filters = []): array
    {
        $query = Donation::query()->where('status', 'completed');

        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (!empty($filters['donor_id'])) {
            $query->where('donor_id', $filters['donor_id']);
        }

        $isSqlite = DB::getDriverName() === 'sqlite';
        $monthExpression = $isSqlite 
            ? "strftime('%Y-%m', donation_date)"
            : "DATE_FORMAT(donation_date, '%Y-%m')";

        return $query
            ->selectRaw("
                {$monthExpression} as month,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
            ")
            ->where('donation_date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get donation type distribution
     */
    public function getDonationTypeDistribution(array $filters = []): array
    {
        $query = Donation::query()
            ->with('donationType')
            ->where('status', 'completed');

        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (!empty($filters['donor_id'])) {
            $query->where('donor_id', $filters['donor_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('donation_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('donation_date', '<=', $filters['date_to']);
        }

        return $query
            ->selectRaw('
                donation_type_id,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->groupBy('donation_type_id')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->donationType->name ?? 'Unknown',
                    'count' => $item->count,
                    'total_amount' => $item->total_amount,
                ];
            })
            ->toArray();
    }

    /**
     * Get top donors
     */
    public function getTopDonors(int $limit = 5, array $filters = []): array
    {
        $query = Donation::query()
            ->with('donor')
            ->where('status', 'completed');

        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('donation_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('donation_date', '<=', $filters['date_to']);
        }

        return $query
            ->selectRaw('
                donor_id,
                COUNT(*) as donation_count,
                SUM(amount) as total_donated
            ')
            ->groupBy('donor_id')
            ->orderByDesc('total_donated')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'donor_name' => $item->donor->full_name ?? 'Unknown',
                    'donation_count' => $item->donation_count,
                    'total_donated' => $item->total_donated,
                ];
            })
            ->toArray();
    }

    /**
     * Process recurring donations that are due
     */
    public function processRecurringDonations(): array
    {
        $processed = [];
        $errors = [];

        // Get the latest donation for each donor/type pair that is recurring
        // This prevents exponential duplication if we just iterated all recurring donations
        $recurringCandidates = Donation::where('is_recurring', true)
            ->where('status', 'completed') // Only consider completed donations as valid for recurrence
            ->selectRaw('donor_id, donation_type_id, MAX(donation_date) as latest_date')
            ->groupBy('donor_id', 'donation_type_id')
            ->get();

        foreach ($recurringCandidates as $candidate) {
            $lastDonation = Donation::where('donor_id', $candidate->donor_id)
                ->where('donation_type_id', $candidate->donation_type_id)
                ->where('donation_date', $candidate->latest_date)
                ->first();

            // Should process if the last donation was more than 30 days ago (or 1 month)
            // Using addMonth()->isPast() ensures we stick to monthly cycles
            if ($lastDonation && $lastDonation->donation_date->addMonth()->isPast()) {
                try {
                    $newDonation = $this->processDonation([
                        'donor_id' => $lastDonation->donor_id,
                        'campaign_id' => $lastDonation->campaign_id,
                        'donation_type_id' => $lastDonation->donation_type_id,
                        'amount' => $lastDonation->amount,
                        'currency_id' => $lastDonation->currency_id,
                        'donation_date' => now(),
                        'is_recurring' => true,
                        // Copy other relevant fields
                        'pledge_amount' => $lastDonation->pledge_amount,
                        'in_kind_description' => $lastDonation->in_kind_description,
                        'payment_method' => $lastDonation->payment_method, // Assume same payment method
                        'status' => 'completed',
                        'notes' => 'Recurring donation processed automatically based on Donation #' . $lastDonation->id,
                    ]);

                    if ($newDonation['success']) {
                        $processed[] = $newDonation['donation']->id;
                    } else {
                        $errors[] = [
                            'donation_id' => $lastDonation->id,
                            'error' => $newDonation['message'],
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'donation_id' => $lastDonation->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'processed_count' => count($processed),
            'processed_ids' => $processed,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get yearly comparison statistics
     */
    public function getYearlyComparison(): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        $getYearStats = function ($year) {
            $stats = Donation::whereYear('donation_date', $year)
                ->where('status', 'completed')
                ->selectRaw('
                    COALESCE(SUM(amount), 0) as total_amount,
                    COUNT(*) as count,
                    COALESCE(AVG(amount), 0) as avg_amount
                ')
                ->first();

            return [
                'year' => $year,
                'total_amount' => (float) ($stats->total_amount ?? 0),
                'count' => (int) ($stats->count ?? 0),
                'avg_amount' => (float) ($stats->avg_amount ?? 0),
            ];
        };

        $current = $getYearStats($currentYear);
        $previous = $getYearStats($previousYear);

        // Calculate percent change
        $totalPercentChange = 0;
        if ($previous['total_amount'] > 0) {
            $totalPercentChange = (($current['total_amount'] - $previous['total_amount']) / $previous['total_amount']) * 100;
        } elseif ($current['total_amount'] > 0) {
            $totalPercentChange = 100;
        }

        $countPercentChange = 0;
        if ($previous['count'] > 0) {
            $countPercentChange = (($current['count'] - $previous['count']) / $previous['count']) * 100;
        } elseif ($current['count'] > 0) {
            $countPercentChange = 100;
        }

        // Monthly breakdown for both years
        $monthlyComparison = [];
        for ($m = 1; $m <= 12; $m++) {
            $month = str_pad($m, 2, '0', STR_PAD_LEFT);
            
            $currMonthStats = Donation::whereYear('donation_date', $currentYear)
                ->whereMonth('donation_date', $m)
                ->where('status', 'completed')
                ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
                ->first();

            $prevMonthStats = Donation::whereYear('donation_date', $previousYear)
                ->whereMonth('donation_date', $m)
                ->where('status', 'completed')
                ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
                ->first();

            $monthlyComparison[] = [
                'month' => "{$currentYear}-{$month}",
                'current' => [
                    'total_amount' => (float) $currMonthStats->total,
                    'count' => (int) $currMonthStats->count,
                ],
                'previous' => [
                    'total_amount' => (float) $prevMonthStats->total,
                    'count' => (int) $prevMonthStats->count,
                ],
            ];
        }

        return [
            'current_year' => $current,
            'previous_year' => $previous,
            'total_percent_change' => $totalPercentChange,
            'count_percent_change' => $countPercentChange,
            'monthly_comparison' => $monthlyComparison,
        ];
    }

    /**
     * Export donations to array format
     */
    public function exportDonations(array $filters = []): array
    {
        $query = Donation::with(['donor', 'campaign', 'donationType', 'currency']);

        // Apply filters
        if (!empty($filters['donor_id'])) {
            $query->where('donor_id', $filters['donor_id']);
        }

        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (!empty($filters['donation_type_id'])) {
            $query->where('donation_type_id', $filters['donation_type_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('donation_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('donation_date', '<=', $filters['date_to']);
        }

        return $query->get()->map(function ($donation) {
            return [
                'ID' => $donation->id,
                'Donor' => $donation->donor->full_name ?? '',
                'Amount' => $donation->amount,
                'Currency' => $donation->currency->code ?? '',
                'Type' => $donation->donationType->name ?? '',
                'Campaign' => $donation->campaign?->title ?? '',
                'Date' => $donation->donation_date->format('Y-m-d'),
                'Recurring' => $donation->is_recurring ? 'Yes' : 'No',
                'Pledge Amount' => $donation->pledge_amount,
                'In-Kind Description' => $donation->in_kind_description,
            ];
        })->toArray();
    }
}
