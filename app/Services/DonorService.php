<?php

namespace App\Services;

use App\Models\Donor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DonorService
{
    /**
     * Get paginated donors with filters.
     */
    public function getPaginatedDonors(array $filters = []): LengthAwarePaginator
    {
        $query = Donor::query()->with('categories');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('organization_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['donor_type'])) {
            $query->where('donor_type', $filters['donor_type']);
        }

        if (!empty($filters['category'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('donor_categories.id', $filters['category']);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Create a new donor.
     */
    public function createDonor(array $data): Donor
    {
        return Donor::create($data);
    }

    /**
     * Update an existing donor.
     */
    public function updateDonor(Donor $donor, array $data): bool
    {
        return $donor->update($data);
    }

    /**
     * Delete a donor.
     */
    public function deleteDonor(Donor $donor): bool
    {
        return $donor->delete();
    }

    /**
     * Get donor statistics.
     */
    public function getDonorStats(): array
    {
        return [
            'total_donors' => Donor::count(),
            'active_donors' => Donor::where('status', 'active')->count(),
            'individual_donors' => Donor::where('donor_type', 'individual')->count(),
            'corporate_donors' => Donor::where('donor_type', 'corporate')->count(),
            'foundation_donors' => Donor::where('donor_type', 'foundation')->count(),
            'new_this_month' => Donor::whereMonth('created_at', now()->month)->count(),
            'total_donated' => Donor::sum('total_donated'),
            'avg_donation' => Donor::where('total_donated', '>', 0)->avg('total_donated') ?? 0,
        ];
    }
}
