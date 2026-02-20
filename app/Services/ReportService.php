<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\Currency;
use App\Models\Donation;
use App\Models\DonationType;
use App\Models\Donor;
use App\Models\DonorCategory;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * Generate PDF Receipt for a donation
     */
    public function generateReceipt(Donation $donation): string
    {
        $pdf = Pdf::loadView('reports.donation-receipt', [
            'donation' => $donation->load(['donor', 'campaign', 'currency']),
        ]);

        return $pdf->output();
    }

    /**
     * Generate a full donations report PDF
     */
    public function generateFullReport($donations): string
    {
        $pdf = Pdf::loadView('reports.donations-full-report', [
            'donations' => $donations,
            'title' => 'Donations Report',
            'date' => now()->format('F j, Y'),
        ]);

        return $pdf->output();
    }
    /**
     * Get Dashboard KPIs
     */
    public function getDashboardKPIs(string $period = 'monthly'): array
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $today = date('Y-m-d');
        $lastMonth = date('Y-m-d', strtotime('-1 month'));
        
        return [
            'total_funds_raised' => $this->getTotalFundsRaised(),
            'active_donors' => $this->getActiveDonorsCount(),
            'recurring_donors' => $this->getRecurringDonorsCount(),
            'monthly_revenue' => $this->getMonthlyRevenue($currentYear, $currentMonth),
            'ytd_revenue' => $this->getYearToDateRevenue($currentYear),
            'avg_donation_amount' => $this->getAverageDonationAmount(),
            'donor_retention_rate' => $this->calculateDonorRetentionRate(),
            'campaign_count' => $this->getActiveCampaignCount(),
            'pledge_balance' => $this->getOutstandingPledgeBalance(),
            'event_revenue' => $this->getEventRevenue($lastMonth, $today),
            'recent_donations' => $this->getRecentDonations(5),
            'top_campaigns' => $this->getTopCampaigns(5),
        ];
    }

    /**
     * Get total funds raised (all time)
     */
    public function getTotalFundsRaised(): float
    {
        return (float) Donation::where('status', 'completed')->sum('amount');
    }

    /**
     * Get count of active donors (donated in last 12 months)
     */
    public function getActiveDonorsCount(): int
    {
        $oneYearAgo = now()->subYear();
        
        return Donation::where('status', 'completed')
            ->where('donation_date', '>=', $oneYearAgo)
            ->distinct('donor_id')
            ->count('donor_id');
    }

    /**
     * Get count of recurring donors
     */
    public function getRecurringDonorsCount(): int
    {
        return Donation::where('status', 'completed')
            ->where('is_recurring', 1)
            ->distinct('donor_id')
            ->count('donor_id');
    }

    /**
     * Get monthly revenue
     */
    public function getMonthlyRevenue($year, $month): float
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return (float) Donation::where('status', 'completed')
            ->whereBetween('donation_date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get year-to-date revenue
     */
    public function getYearToDateRevenue($year): float
    {
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = now()->endOfDay();
        
        return (float) Donation::where('status', 'completed')
            ->whereBetween('donation_date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get average donation amount
     */
    public function getAverageDonationAmount(): float
    {
        return (float) Donation::where('status', 'completed')->avg('amount');
    }

    /**
     * Calculate donor retention rate
     */
    public function calculateDonorRetentionRate(): float
    {
        $oneYearAgo = now()->subYear()->startOfDay();
        $twoYearsAgo = now()->subYears(2)->startOfDay();
        
        // Get donors who donated in the previous year (year before last)
        $previousYearDonorIds = Donation::where('status', 'completed')
            ->whereBetween('donation_date', [$twoYearsAgo, $oneYearAgo->copy()->subSecond()])
            ->distinct()
            ->pluck('donor_id');
            
        if ($previousYearDonorIds->isEmpty()) {
            return 0;
        }
        
        // Get donors from previous year who also donated in current year
        $retainedDonorsCount = Donation::where('status', 'completed')
            ->whereIn('donor_id', $previousYearDonorIds)
            ->where('donation_date', '>=', $oneYearAgo)
            ->distinct('donor_id')
            ->count('donor_id');
        
        return ($retainedDonorsCount / $previousYearDonorIds->count()) * 100;
    }

    /**
     * Get active campaign count
     */
    public function getActiveCampaignCount(): int
    {
        return Campaign::where('status', 'active')->count();
    }

    /**
     * Get outstanding pledge balance
     */
    public function getOutstandingPledgeBalance(): float
    {
        // Pledges are stored in donations table where pledge_amount > 0
        return (float) Donation::where('status', 'completed')
            ->whereNotNull('pledge_amount')
            ->whereRaw('pledge_amount > amount')
            ->sum(DB::raw('pledge_amount - amount'));
    }

    /**
     * Get event revenue for period
     */
    public function getEventRevenue($startDate, $endDate): float
    {
        return (float) CampaignEvent::whereBetween('event_date', [$startDate, $endDate])
            ->sum('funds_raised');
    }

    /**
     * Get donor retention analysis
     */
    public function getDonorRetentionAnalysis(string $period = 'monthly', int $range = 12): array
    {
        $analysis = [];
        
        for ($i = $range - 1; $i >= 0; $i--) {
            $currentStart = now()->subMonths($i)->startOfMonth();
            $currentEnd = $currentStart->copy()->endOfMonth();
            
            $previousStart = $currentStart->copy()->subMonths(1)->startOfMonth();
            $previousEnd = $previousStart->copy()->endOfMonth();
            
            if ($period === 'quarterly') {
                $currentStart = now()->subMonths($i * 3)->startOfQuarter();
                $currentEnd = $currentStart->copy()->endOfQuarter();
                $previousStart = $currentStart->copy()->subQuarter()->startOfQuarter();
                $previousEnd = $previousStart->copy()->endOfQuarter();
            } elseif ($period === 'yearly') {
                $currentStart = now()->subYears($i)->startOfYear();
                $currentEnd = $currentStart->copy()->endOfYear();
                $previousStart = $currentStart->copy()->subYear()->startOfYear();
                $previousEnd = $previousStart->copy()->endOfYear();
            }

            $currentDonorIds = Donation::where('status', 'completed')
                ->whereBetween('donation_date', [$currentStart, $currentEnd])
                ->distinct()
                ->pluck('donor_id');
                
            $previousDonorIds = Donation::where('status', 'completed')
                ->whereBetween('donation_date', [$previousStart, $previousEnd])
                ->distinct()
                ->pluck('donor_id');

            $returningDonors = $currentDonorIds->intersect($previousDonorIds);
            $newDonors = $currentDonorIds->diff($previousDonorIds);
            
            $periodLabel = $currentStart->format('Y-m');
            if ($period === 'quarterly') {
                $periodLabel = $currentStart->format('Y') . '-Q' . ceil($currentStart->month / 3);
            } elseif ($period === 'yearly') {
                $periodLabel = $currentStart->format('Y');
            }
            
            $analysis[$periodLabel] = [
                'period' => $periodLabel,
                'new_donors' => $newDonors->count(),
                'returning_donors' => $returningDonors->count(),
                'total_donors' => $currentDonorIds->count(),
                'retention_rate' => $previousDonorIds->count() > 0 
                    ? ($returningDonors->count() / $previousDonorIds->count()) * 100 
                    : 0,
            ];
        }
        
        return $analysis;
    }

    /**
     * Get churn analysis
     */
    public function getChurnAnalysis(string $period = 'monthly'): array
    {
        $analysis = [];
        $range = 12;
        
        for ($i = $range - 1; $i >= 0; $i--) {
            $periodStart = now()->subMonths($i)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            
            $nextPeriodStart = $periodStart->copy()->addMonth()->startOfMonth();
            $nextPeriodEnd = $nextPeriodStart->copy()->endOfMonth();

            if ($period === 'quarterly') {
                $periodStart = now()->subMonths($i * 3)->startOfQuarter();
                $periodEnd = $periodStart->copy()->endOfQuarter();
                $nextPeriodStart = $periodStart->copy()->addQuarter()->startOfQuarter();
                $nextPeriodEnd = $nextPeriodStart->copy()->endOfQuarter();
            }

            $periodDonorIds = Donation::where('status', 'completed')
                ->whereBetween('donation_date', [$periodStart, $periodEnd])
                ->distinct()
                ->pluck('donor_id');
                
            $nextPeriodDonorIds = Donation::where('status', 'completed')
                ->whereBetween('donation_date', [$nextPeriodStart, $nextPeriodEnd])
                ->distinct()
                ->pluck('donor_id');

            $churnedDonors = $periodDonorIds->diff($nextPeriodDonorIds);
            
            $periodLabel = $periodStart->format('Y-m');
            
            $analysis[$periodLabel] = [
                'period' => $periodLabel,
                'total_donors' => $periodDonorIds->count(),
                'churned_donors' => $churnedDonors->count(),
                'churn_rate' => $periodDonorIds->count() > 0 
                    ? ($churnedDonors->count() / $periodDonorIds->count()) * 100 
                    : 0,
            ];
        }
        
        return $analysis;
    }

    /**
     * Get recurring donation forecast
     */
    public function getRecurringDonationForecast(int $months = 12): array
    {
        $forecast = [];
        
        $activeRecurring = Donation::where('status', 'completed')
            ->where('is_recurring', 1)
            ->with(['donor', 'campaign', 'donationType'])
            ->get();
            
        $monthlyRevenue = [];
        
        foreach ($activeRecurring as $donation) {
            $amount = (float) $donation->amount;
            // CI4 code uses last donation date, let's use now as start point for forecast
            
            for ($i = 1; $i <= $months; $i++) {
                $forecastDate = now()->addMonths($i)->format('Y-m');
                
                if (!isset($monthlyRevenue[$forecastDate])) {
                    $monthlyRevenue[$forecastDate] = [
                        'month' => $forecastDate,
                        'expected_amount' => 0,
                        'donation_count' => 0,
                    ];
                }
                
                $monthlyRevenue[$forecastDate]['expected_amount'] += $amount;
                $monthlyRevenue[$forecastDate]['donation_count']++;
            }
        }
        
        ksort($monthlyRevenue);
        return array_values($monthlyRevenue);
    }

    /**
     * Get donor segmentation
     */
    public function getDonorSegmentation(string $segmentBy = 'category'): array
    {
        switch ($segmentBy) {
            case 'category':
                return $this->getSegmentationByCategory();
            case 'type':
                return $this->getSegmentationByType();
            case 'amount':
                return $this->getSegmentationByAmount();
            case 'frequency':
                return $this->getSegmentationByFrequency();
            case 'location':
                return $this->getSegmentationByLocation();
            default:
                return [];
        }
    }

    private function getSegmentationByCategory(): array
    {
        return DonorCategory::withCount(['donors as donor_count'])
            ->get()
            ->map(function ($category) {
                $stats = DB::table('donations')
                    ->join('donor_category_donor', 'donations.donor_id', '=', 'donor_category_donor.donor_id')
                    ->where('donor_category_donor.donor_category_id', $category->id)
                    ->where('donations.status', 'completed')
                    ->selectRaw('COUNT(donations.id) as donation_count, SUM(donations.amount) as total_amount, AVG(donations.amount) as avg_amount')
                    ->first();

                return [
                    'segment' => $category->name,
                    'donor_count' => $category->donor_count,
                    'donation_count' => $stats->donation_count ?? 0,
                    'total_amount' => (float) ($stats->total_amount ?? 0),
                    'avg_amount' => (float) ($stats->avg_amount ?? 0),
                ];
            })->toArray();
    }

    private function getSegmentationByType(): array
    {
        return DB::table('donors')
            ->selectRaw('donor_type as segment, COUNT(DISTINCT donors.id) as donor_count')
            ->leftJoin('donations', function($join) {
                $join->on('donors.id', '=', 'donations.donor_id')
                    ->where('donations.status', '=', 'completed');
            })
            ->selectRaw('COUNT(donations.id) as donation_count, SUM(donations.amount) as total_amount, AVG(donations.amount) as avg_amount')
            ->groupBy('donor_type')
            ->get()
            ->map(fn($item) => (array)$item)
            ->toArray();
    }

    private function getSegmentationByAmount(): array
    {
        $segments = [
            ['label' => 'No Donations', 'min' => 0, 'max' => 0],
            ['label' => 'Under $100', 'min' => 0.01, 'max' => 99.99],
            ['label' => '$100 - $500', 'min' => 100, 'max' => 499.99],
            ['label' => '$500 - $1,000', 'min' => 500, 'max' => 999.99],
            ['label' => '$1,000 - $5,000', 'min' => 1000, 'max' => 4999.99],
            ['label' => 'Over $5,000', 'min' => 5000, 'max' => PHP_FLOAT_MAX],
        ];

        $donorTotals = Donation::where('status', 'completed')
            ->groupBy('donor_id')
            ->selectRaw('donor_id, SUM(amount) as total')
            ->pluck('total', 'donor_id')
            ->toArray();

        // Include donors with 0 donations
        $allDonorIds = Donor::pluck('id')->toArray();
        foreach ($allDonorIds as $id) {
            if (!isset($donorTotals[$id])) {
                $donorTotals[$id] = 0;
            }
        }

        $results = [];
        foreach ($segments as $segment) {
            $count = 0;
            $sum = 0;
            foreach ($donorTotals as $total) {
                if ($total >= $segment['min'] && $total <= $segment['max']) {
                    $count++;
                    $sum += $total;
                }
            }
            $results[] = [
                'segment' => $segment['label'],
                'donor_count' => $count,
                'total_amount' => $sum,
                'avg_amount' => $count > 0 ? $sum / $count : 0,
            ];
        }

        return $results;
    }

    private function getSegmentationByFrequency(): array
    {
        $donorCounts = Donation::where('status', 'completed')
            ->groupBy('donor_id')
            ->selectRaw('donor_id, COUNT(id) as count, SUM(amount) as total')
            ->get();

        $segments = [
            'One-time Donor' => ['min' => 1, 'max' => 1],
            'Occasional (2-5)' => ['min' => 2, 'max' => 5],
            'Frequent (6-12)' => ['min' => 6, 'max' => 12],
            'Major Donor (13+)' => ['min' => 13, 'max' => PHP_INT_MAX],
        ];

        $results = [];
        foreach ($segments as $label => $range) {
            $filtered = $donorCounts->filter(fn($d) => $d->count >= $range['min'] && $d->count <= $range['max']);
            $results[] = [
                'segment' => $label,
                'donor_count' => $filtered->count(),
                'donation_count' => $filtered->sum('count'),
                'total_amount' => $filtered->sum('total'),
                'avg_amount' => $filtered->count() > 0 ? $filtered->sum('total') / $filtered->count() : 0,
            ];
        }
        
        // Add "No Donations"
        $totalDonors = Donor::count();
        $donorsWithDonations = $donorCounts->count();
        $noDonationsCount = $totalDonors - $donorsWithDonations;
        
        array_unshift($results, [
            'segment' => 'No Donations',
            'donor_count' => $noDonationsCount,
            'donation_count' => 0,
            'total_amount' => 0,
            'avg_amount' => 0,
        ]);

        return $results;
    }

    private function getSegmentationByLocation(): array
    {
        return DB::table('donors')
            ->selectRaw('COALESCE(country, "Unknown") as segment, COUNT(DISTINCT donors.id) as donor_count')
            ->leftJoin('donations', function($join) {
                $join->on('donors.id', '=', 'donations.donor_id')
                    ->where('donations.status', '=', 'completed');
            })
            ->selectRaw('COUNT(donations.id) as donation_count, SUM(donations.amount) as total_amount, AVG(donations.amount) as avg_amount')
            ->groupBy('segment')
            ->get()
            ->map(fn($item) => (array)$item)
            ->toArray();
    }

    /**
     * Get monthly donation trend
     */
    public function getMonthlyDonationTrend(int $months = 12): array
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $monthExpression = $isSqlite 
            ? "strftime('%Y-%m', donation_date)"
            : "DATE_FORMAT(donation_date, '%Y-%m')";

        return Donation::where('status', 'completed')
            ->where('donation_date', '>=', now()->subMonths($months))
            ->selectRaw("
                {$monthExpression} as month,
                COUNT(*) as donation_count,
                SUM(amount) as total_amount,
                COUNT(DISTINCT donor_id) as donor_count
            ")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get recent donations
     */
    public function getRecentDonations(int $limit = 10): array
    {
        return Donation::with(['donor', 'campaign'])
            ->orderBy('donation_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($donation) {
                return [
                    'first_name' => $donation->donor->first_name ?? '',
                    'last_name' => $donation->donor->last_name ?? '',
                    'amount' => (float) $donation->amount,
                    'donation_date' => $donation->donation_date,
                    'campaign_title' => $donation->campaign->title ?? 'General',
                    'is_recurring' => $donation->is_recurring,
                ];
            })
            ->toArray();
    }

    /**
     * Get top performing campaigns
     */
    public function getTopCampaigns(int $limit = 10): array
    {
        return Campaign::orderBy('total_raised', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($campaign) {
                $progress = $campaign->goal_amount > 0 
                    ? ($campaign->total_raised / $campaign->goal_amount) * 100 
                    : 0;
                    
                return [
                    'title' => $campaign->title,
                    'total_raised' => (float) $campaign->total_raised,
                    'goal_amount' => (float) $campaign->goal_amount,
                    'progress' => $progress,
                    'donor_count' => $campaign->donor_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get Campaign Performance Report Data
     */
    public function getCampaignPerformanceData(array $filters = []): array
    {
        $query = Campaign::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        $campaigns = $query->get()->map(function ($campaign) {
            $donationCount = Donation::where('campaign_id', $campaign->id)->where('status', 'completed')->count();
            $progress = $campaign->goal_amount > 0 
                ? ($campaign->total_raised / $campaign->goal_amount) * 100 
                : 0;

            return [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'goal_amount' => (float) $campaign->goal_amount,
                'total_raised' => (float) $campaign->total_raised,
                'progress_percentage' => (float) $progress,
                'donor_count' => $campaign->donor_count,
                'donation_count' => $donationCount,
                'start_date' => $campaign->start_date,
                'end_date' => $campaign->end_date,
                'currency_code' => $campaign->currency->code ?? 'USD',
            ];
        });

        return $campaigns->toArray();
    }

    /**
     * Get Donations Report Data
     */
    public function getDonationReportData(array $filters = []): array
    {
        $query = Donation::with(['donor', 'campaign', 'donationType', 'currency']);

        if (!empty($filters['date_from'])) {
            $query->where('donation_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('donation_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (!empty($filters['donation_type_id'])) {
            $query->where('donation_type_id', $filters['donation_type_id']);
        }

        if (!empty($filters['donor_type'])) {
            $query->whereHas('donor', function ($q) use ($filters) {
                $q->where('donor_type', $filters['donor_type']);
            });
        }

        if (isset($filters['is_recurring']) && $filters['is_recurring'] !== '') {
            $query->where('is_recurring', $filters['is_recurring']);
        }

        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        return $query->get()->map(function ($donation) {
            return [
                'id' => $donation->id,
                'amount' => (float) $donation->amount,
                'donation_date' => $donation->donation_date,
                'donor_name' => $donation->donor->full_name ?? ($donation->donor->first_name . ' ' . $donation->donor->last_name),
                'donor_email' => $donation->donor->email ?? '',
                'donor_type' => $donation->donor->donor_type ?? '',
                'campaign_title' => $donation->campaign->title ?? 'General',
                'donation_type' => $donation->donationType->name ?? 'N/A',
                'is_recurring' => $donation->is_recurring,
                'currency_code' => $donation->currency->code ?? 'USD',
                'payment_method' => $donation->payment_method,
                'status' => $donation->status,
            ];
        })->toArray();
    }
}
