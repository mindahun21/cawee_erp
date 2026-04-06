<?php

namespace App\Filament\Pages;

use App\Models\FileAccessLog;
use App\Models\FileShare;
use App\Models\SharedFile;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class FileSharingReports extends Page
{
    protected string $view = 'filament.pages.file-sharing-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'File Sharing';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'File Sharing Reports';

    public array $summary = [];

    public Collection $topFiles;

    public Collection $recentActivity;

    public Collection $deniedAccess;

    public Collection $uploadsByUser;

    public array $activityByAction = [];

    public array $deniedByDay = [];

    public string $rangeDays = '30';

    public function mount(): void
    {
        $this->loadReportData();
    }

    public function setRange(string $days): void
    {
        $allowed = ['7', '30', '90', 'all'];

        $this->rangeDays = in_array($days, $allowed, true) ? $days : '30';
        $this->loadReportData();
    }

    public function exportRecentActivityCsv(): StreamedResponse
    {
        $rows = $this->baseAccessLogQuery()
            ->with(['file', 'share', 'user'])
            ->latest('accessed_at')
            ->limit(500)
            ->get();

        $filename = 'file-sharing-recent-activity-' . $this->rangeLabel() . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Action', 'File', 'Share Type', 'Actor', 'IP', 'Date/Time', 'Notes']);

            foreach ($rows as $log) {
                fputcsv($handle, [
                    $log->action,
                    $log->file?->display_name ?? '',
                    $log->share?->share_type ?? '',
                    $log->user?->name ?? 'Guest',
                    $log->ip_address ?? '',
                    optional($log->accessed_at)->format('Y-m-d H:i:s'),
                    $log->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportDeniedAccessCsv(): StreamedResponse
    {
        $rows = $this->baseAccessLogQuery()
            ->with(['file', 'share', 'user'])
            ->whereNotNull('notes')
            ->where('notes', 'like', 'Denied access:%')
            ->latest('accessed_at')
            ->limit(500)
            ->get();

        $filename = 'file-sharing-denied-access-' . $this->rangeLabel() . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['File', 'Share Type', 'Actor', 'IP', 'Date/Time', 'Reason']);

            foreach ($rows as $log) {
                fputcsv($handle, [
                    $log->file?->display_name ?? '',
                    $log->share?->share_type ?? '',
                    $log->user?->name ?? 'Guest',
                    $log->ip_address ?? '',
                    optional($log->accessed_at)->format('Y-m-d H:i:s'),
                    $log->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function loadReportData(): void
    {
        $from = $this->rangeStart();

        $this->summary = [
            'files' => SharedFile::query()->count(),
            'activeShares' => FileShare::query()->where('is_active', true)->count(),
            'downloads' => FileAccessLog::query()
                ->where('action', 'downloaded')
                ->when($from, fn (Builder $query) => $query->where('accessed_at', '>=', $from))
                ->count(),
            'expiredShares' => FileShare::query()->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'publicShares' => FileShare::query()->where('share_type', 'public')->count(),
            'staffShares' => FileShare::query()->where('share_type', 'staff')->count(),
            'clientShares' => FileShare::query()->where('share_type', 'client')->count(),
        ];

        $this->topFiles = SharedFile::query()
            ->withCount([
                'accessLogs as downloads_count' => fn ($query) => $query
                    ->where('action', 'downloaded')
                    ->when($from, fn (Builder $query) => $query->where('accessed_at', '>=', $from)),
            ])
            ->orderByDesc('downloads_count')
            ->limit(5)
            ->get();

        $this->recentActivity = FileAccessLog::query()
            ->with(['file', 'share', 'user'])
            ->when($from, fn (Builder $query) => $query->where('accessed_at', '>=', $from))
            ->latest('accessed_at')
            ->limit(8)
            ->get();

        $this->deniedAccess = FileAccessLog::query()
            ->with(['file', 'share', 'user'])
            ->whereNotNull('notes')
            ->where('notes', 'like', 'Denied access:%')
            ->when($from, fn (Builder $query) => $query->where('accessed_at', '>=', $from))
            ->latest('accessed_at')
            ->limit(8)
            ->get();

        $this->uploadsByUser = SharedFile::query()
            ->selectRaw('uploaded_by, COUNT(*) as uploads_count')
            ->with('uploader')
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->groupBy('uploaded_by')
            ->orderByDesc('uploads_count')
            ->limit(5)
            ->get();

        $this->activityByAction = FileAccessLog::query()
            ->selectRaw('action, COUNT(*) as total')
            ->when($from, fn (Builder $query) => $query->where('accessed_at', '>=', $from))
            ->groupBy('action')
            ->pluck('total', 'action')
            ->toArray();

        $deniedStart = $this->deniedWindowStart($from);

        $deniedByDay = FileAccessLog::query()
            ->selectRaw('DATE(accessed_at) as day, COUNT(*) as total')
            ->whereNotNull('notes')
            ->where('notes', 'like', 'Denied access:%')
            ->where('accessed_at', '>=', $deniedStart)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $labels = collect(range(0, 6))
            ->map(fn (int $offset) => $deniedStart->copy()->addDays($offset));

        $this->deniedByDay = $labels
            ->mapWithKeys(function (Carbon $day) use ($deniedByDay): array {
                $key = $day->toDateString();

                return [
                    $day->format('M d') => (int) ($deniedByDay[$key] ?? 0),
                ];
            })
            ->toArray();
    }

    protected function rangeStart(): ?Carbon
    {
        if ($this->rangeDays === 'all') {
            return null;
        }

        return now()->subDays(((int) $this->rangeDays) - 1)->startOfDay();
    }

    protected function deniedWindowStart(?Carbon $from): Carbon
    {
        if ($from === null) {
            return now()->subDays(6)->startOfDay();
        }

        $now = now()->startOfDay();
        $days = max(1, $from->diffInDays($now) + 1);
        $windowDays = min(7, $days);

        return $now->copy()->subDays($windowDays - 1)->startOfDay();
    }

    protected function baseAccessLogQuery(): Builder
    {
        return FileAccessLog::query()
            ->when($this->rangeStart(), fn (Builder $query, Carbon $from) => $query->where('accessed_at', '>=', $from));
    }

    protected function rangeLabel(): string
    {
        return match ($this->rangeDays) {
            '7' => '7d',
            '30' => '30d',
            '90' => '90d',
            default => 'all',
        };
    }
}
