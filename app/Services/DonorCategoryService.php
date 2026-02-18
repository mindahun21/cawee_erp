<?php

namespace App\Services;

use App\Models\Donor;
use App\Models\DonorCategory;
use Illuminate\Support\Collection;

class DonorCategoryService
{
    /**
     * Get all categories with donor counts.
     */
    public function getAllCategories(bool $withCount = false): Collection
    {
        $query = DonorCategory::query();

        if ($withCount) {
            $query->withCount('donors');
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Assign categories to a donor.
     */
    public function assignCategoriesToDonor(Donor $donor, array $categoryIds): void
    {
        $donor->categories()->sync($categoryIds);
    }

    /**
     * Create a new category.
     */
    public function createCategory(array $data): DonorCategory
    {
        return DonorCategory::create($data);
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(DonorCategory $category, array $data): bool
    {
        return $category->update($data);
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(DonorCategory $category): bool
    {
        if ($category->donors()->exists()) {
            return false;
        }

        return $category->delete();
    }
}
