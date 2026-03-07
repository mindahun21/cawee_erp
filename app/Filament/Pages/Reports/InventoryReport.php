<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Department;
use App\Models\InventoryMovement;
use App\Models\DepreciationLog;
use App\Models\AssetAssignment;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\GeneralReportExport;

class InventoryReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected string $view = 'filament.pages.reports.inventory-report';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Inventory and Asset';
    
    protected static ?int $navigationSort = 6;
    
    protected static ?string $title = 'Inventory & Asset Reports';
    protected static ?string $navigationLabel = 'Report';

    public string $activeTab = 'valuation';
    public ?array $data = [];
    public $reportData = [];

    public function mount(): void
    {
        $this->form->fill([
            'fromDate' => now()->startOfMonth()->format('Y-m-d'),
            'toDate' => now()->endOfMonth()->format('Y-m-d'),
        ]);
        $this->loadReport();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 4,
                    'lg' => 5,
                ])
                ->schema([
                    Select::make('locationId')
                        ->label('Warehouse/Location')
                        ->options(Location::pluck('location_name', 'id'))
                        ->live()
                        ->afterStateUpdated(fn () => $this->loadReport()),
                    Select::make('categoryId')
                        ->label('Category')
                        ->options(AssetCategory::pluck('name', 'id'))
                        ->live()
                        ->afterStateUpdated(fn () => $this->loadReport()),
                    DatePicker::make('fromDate')
                        ->label('From date')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live()
                        ->afterStateUpdated(fn () => $this->loadReport()),
                    DatePicker::make('toDate')
                        ->label('To date')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live()
                        ->afterStateUpdated(fn () => $this->loadReport()),
                ])
            ])
            ->statePath('data');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $filters = $this->data;
        
        match ($this->activeTab) {
            'valuation' => $this->loadValuation($filters),
            'depreciation' => $this->loadDepreciation($filters),
            'aging' => $this->loadAging($filters),
            'utilization' => $this->loadUtilization($filters),
            'movement' => $this->loadMovement($filters),
            'damaged' => $this->loadDamaged($filters),
            'location' => $this->loadLocationWise($filters),
            'category' => $this->loadCategoryWise($filters),
            'turnover' => $this->loadTurnover($filters),
            default => $this->loadValuation($filters),
        };
    }

    protected function loadValuation(array $filters): void
    {
        $query = Asset::query();
        if ($filters['categoryId'] ?? null) $query->where('asset_category_id', $filters['categoryId']);
        if ($filters['locationId'] ?? null) $query->where('location_id', $filters['locationId']);

        $assets = $query->get();
        $this->reportData = [
            'total_purchase_cost' => $assets->sum('purchase_cost'),
            'current_market_value' => $assets->sum(fn($a) => $a->current_value),
            'assets' => $assets->map(fn($a) => [
                'name' => $a->name,
                'code' => $a->barcode,
                'cost' => $a->purchase_cost,
                'current' => $a->current_value,
                'status' => $a->status,
            ]),
        ];
    }

    protected function loadDepreciation(array $filters): void
    {
        $query = DepreciationLog::with('asset');
        if ($filters['fromDate'] ?? null) $query->where('period_date', '>=', $filters['fromDate']);
        if ($filters['toDate'] ?? null) $query->where('period_date', '<=', $filters['toDate']);
        
        $this->reportData = $query->latest('period_date')->get();
    }

    protected function loadAging(array $filters): void
    {
        $query = Asset::query();
        if ($filters['categoryId'] ?? null) $query->where('asset_category_id', $filters['categoryId']);

        $this->reportData = $query->whereNotNull('purchase_date')->get()->map(fn($a) => [
            'name' => $a->name,
            'purchase_date' => $a->purchase_date,
            'age_years' => $a->purchase_date->diffInYears(now()),
            'useful_life' => $a->useful_life,
            'remaining_life' => max(0, $a->useful_life - $a->purchase_date->diffInYears(now())),
        ]);
    }

    protected function loadUtilization(array $filters): void
    {
        $this->reportData = Asset::withCount(['assignments' => fn($q) => $q->whereNull('returned_date')])
            ->get()->map(fn($a) => [
                'name' => $a->name,
                'total_qty' => $a->quantity,
                'assigned_qty' => $a->assignments_count,
                'utilization_rate' => $a->quantity > 0 ? ($a->assignments_count / $a->quantity) * 100 : 0,
            ]);
    }

    protected function loadMovement(array $filters): void
    {
        $query = InventoryMovement::with(['asset', 'fromLocation', 'toLocation']);
        if ($filters['fromDate'] ?? null) $query->where('date', '>=', $filters['fromDate']);
        if ($filters['toDate'] ?? null) $query->where('date', '<=', $filters['toDate']);
        
        $this->reportData = $query->latest('date')->get();
    }

    protected function loadDamaged(array $filters): void
    {
        $this->reportData = Asset::whereIn('status', ['lost'])
            ->orWhereIn('condition', ['Poor', 'Broken'])
            ->get();
    }

    protected function loadLocationWise(array $filters): void
    {
        $this->reportData = Location::withCount('assets')
            ->get()->map(fn($l) => [
                'name' => $l->location_name,
                'asset_count' => $l->assets_count,
                'total_value' => Asset::where('location_id', $l->id)->sum('purchase_cost'),
            ]);
    }

    protected function loadCategoryWise(array $filters): void
    {
        $this->reportData = AssetCategory::withCount('assets')
            ->get()->map(fn($c) => [
                'name' => $c->name,
                'asset_count' => $c->assets_count,
                'total_value' => Asset::where('asset_category_id', $c->id)->sum('purchase_cost'),
            ]);
    }

    protected function loadTurnover(array $filters): void
    {
        // Simple turnover: Outgoing Qty / Average Stock
        $this->reportData = Asset::all()->map(function($a) {
            $outgoing = InventoryMovement::where('asset_id', $a->id)
                ->whereIn('type', ['Stock Out', 'Disposal', 'Damage'])
                ->sum('quantity');
            
            return [
                'name' => $a->name,
                'outgoing' => $outgoing,
                'current_stock' => $a->quantity,
                'turnover_ratio' => $a->quantity > 0 ? $outgoing / $a->quantity : 0,
            ];
        });
    }

    public function export(string $format)
    {
        $headers = [];
        $data = [];
        $title = "";

        switch ($this->activeTab) {
            case 'valuation':
                $headers = ['Asset Name', 'Barcode', 'Purchase Cost', 'Current Value', 'Status'];
                $data = Asset::all()->map(fn($a) => [
                    $a->name, $a->barcode, number_format($a->purchase_cost, 2), number_format($a->current_value, 2), $a->status
                ])->toArray();
                $title = "ASSET VALUATION REPORT";
                break;
            case 'depreciation':
                $headers = ['Period Date', 'Asset Name', 'Depreciation Amount', 'Book Value'];
                $data = DepreciationLog::with('asset')->get()->map(fn($l) => [
                    $l->period_date->format('M Y'), $l->asset->name, number_format($l->depreciation_amount, 2), number_format($l->book_value, 2)
                ])->toArray();
                $title = "DEPRECIATION REPORT";
                break;
            case 'aging':
                $headers = ['Asset Name', 'Purchase Date', 'Age (Years)', 'Remaining (Years)', 'Useful Life'];
                $data = Asset::whereNotNull('purchase_date')->get()->map(fn($a) => [
                    $a->name, $a->purchase_date->format('d/m/Y'), $a->purchase_date->diffInYears(now()), max(0, $a->useful_life - $a->purchase_date->diffInYears(now())), $a->useful_life
                ])->toArray();
                $title = "ASSET AGING REPORT";
                break;
            case 'utilization':
                $headers = ['Asset Name', 'Total Stock', 'Assigned', 'Utilization Rate (%)'];
                $data = Asset::withCount(['assignments' => fn($q) => $q->whereNull('returned_date')])->get()->map(fn($a) => [
                    $a->name, $a->quantity, $a->assignments_count, number_format($a->quantity > 0 ? ($a->assignments_count / $a->quantity) * 100 : 0, 1) . '%'
                ])->toArray();
                $title = "UTILIZATION REPORT";
                break;
            case 'movement':
                $headers = ['Date', 'Asset', 'Type', 'Qty', 'Origin', 'Destination'];
                $data = InventoryMovement::with(['asset', 'fromLocation', 'toLocation'])->latest()->get()->map(fn($m) => [
                    $m->date->format('d/m/Y'), $m->asset->name, $m->type, $m->quantity, $m->fromLocation->location_name ?? 'N/A', $m->toLocation->location_name ?? 'N/A'
                ])->toArray();
                $title = "INVENTORY MOVEMENT REPORT";
                break;
            case 'damaged':
                $headers = ['Asset Name', 'Status', 'Condition', 'Location'];
                $data = Asset::whereIn('status', ['lost'])->orWhereIn('condition', ['Poor', 'Broken'])->get()->map(fn($a) => [
                    $a->name, $a->status, $a->condition, $a->location->location_name ?? 'N/A'
                ])->toArray();
                $title = "LOST/DAMAGED ASSET REPORT";
                break;
            case 'location':
                $headers = ['Location Name', 'Asset Count', 'Financial Value'];
                $data = Location::withCount('assets')->get()->map(fn($l) => [
                    $l->location_name, $l->assets_count, number_format(Asset::where('location_id', $l->id)->sum('purchase_cost'), 2)
                ])->toArray();
                $title = "LOCATION-WISE ASSET REPORT";
                break;
            case 'category':
                $headers = ['Category Name', 'Asset Count', 'Financial Value'];
                $data = AssetCategory::withCount('assets')->get()->map(fn($c) => [
                    $c->name, $c->assets_count, number_format(Asset::where('asset_category_id', $c->id)->sum('purchase_cost'), 2)
                ])->toArray();
                $title = "CATEGORY-WISE ASSET REPORT";
                break;
            case 'turnover':
                $headers = ['Asset Name', 'Units Out', 'Available Stock', 'Turnover Ratio'];
                $data = Asset::all()->map(function($a) {
                    $outgoing = InventoryMovement::where('asset_id', $a->id)->whereIn('type', ['Stock Out', 'Disposal', 'Damage'])->sum('quantity');
                    return [$a->name, $outgoing, $a->quantity, number_format($a->quantity > 0 ? $outgoing / $a->quantity : 0, 2) . 'x'];
                })->toArray();
                $title = "INVENTORY TURNOVER REPORT";
                break;
        }

        $filename = "report-" . str_replace('/', '-', strtolower($this->activeTab)) . "-" . now()->format('YmdHis');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.generic-pdf', compact('headers', 'data', 'title'));
            return response()->streamDownload(fn() => print($pdf->output()), "{$filename}.pdf");
        }

        return Excel::download(
            new GeneralReportExport($data, $headers),
            "{$filename}." . ($format === 'excel' ? 'xlsx' : 'csv')
        );
    }
}

