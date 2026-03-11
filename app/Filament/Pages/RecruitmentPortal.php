<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\RecruitmentCampaign;
use BackedEnum;
use UnitEnum;

class RecruitmentPortal extends Page
// {
//     protected string $view = 'filament.pages.recruitment-portal';
//     protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

//     protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';


//     public $search = '';
//     public $jobCategory = '';
//     public $jobType = '';
//     public $searched = false;

//     // Allow public access (remove auth middleware)
//     protected function getDefaultMiddleware(): array
//     {
//         return []; // No middleware = public
//     }

//     public function getCampaignsProperty()
//     {
//         return RecruitmentCampaign::query()
//             ->when(
//                 $this->search,
//                 fn($q) =>
//                 $q->where(function ($query) {
//                     $query->where('position', 'like', '%' . $this->search . '%')
//                         ->orWhere('company', 'like', '%' . $this->search . '%')
//                         ->orWhere('department', 'like', '%' . $this->search . '%');
//                 })
//             )
//             ->when($this->jobCategory, fn($q) => $q->where('department', $this->jobCategory))
//             ->when($this->jobType, fn($q) => $q->where('working_form', $this->jobType))
//             ->latest()
//             ->get();
//     }

//     public function getJobCategoriesProperty()
//     {
//         return RecruitmentCampaign::query()
//             ->select('department')
//             ->distinct()
//             ->pluck('department')
//             ->filter()
//             ->values();
//     }

//     public function getJobTypesProperty()
//     {
//         return RecruitmentCampaign::query()
//             ->select('working_form')
//             ->distinct()
//             ->pluck('working_form')
//             ->filter()
//             ->values();
//     }
// }
{
    protected string $view = 'filament.pages.recruitment-portal';
    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationLabel = 'Portal';


    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public function mount(): void
    {
        // Redirect to the public jobs route
        redirect()->route('jobs.index');
    }
}
