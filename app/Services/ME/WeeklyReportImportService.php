<?php

namespace App\Services\ME;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class WeeklyReportImportService
{
    private const PLAN_KEYWORDS = [
        'ዕቅድ',
        'የታቀደ',
        'plan',
        'planned',
        'target',
    ];

    private const ACTUAL_KEYWORDS = [
        'ክንውን',
        'የተገኘ',
        'የተሰጠ',
        'የተመለሰ',
        'actual',
        'achieved',
    ];

    private array $projectCodeCache = [];
    private array $projectNameCache = [];
    private array $periodCache = [];
    private array $indicatorCodeCache = [];
    private array $indicatorLabelCache = [];
    private array $indicatorCreated = [];
    private array $reportColumns = [];
    private array $targetColumns = [];
    private array $indicatorColumns = [];
    private array $projectColumns = [];
    private array $periodColumns = [];

    public function import(string $filePath): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        if (! class_exists(IOFactory::class)) {
            throw new RuntimeException('Spreadsheet import dependency missing: phpoffice/phpspreadsheet.');
        }

        if (! Schema::hasTable('me_indicators') || ! Schema::hasTable('me_indicator_reports') || ! Schema::hasTable('me_indicator_targets')) {
            throw new RuntimeException('M&E core tables are missing. Run migrations first.');
        }

        $hasProjects = Schema::hasTable('me_projects');
        $hasPeriods = Schema::hasTable('me_reporting_periods');

        $this->primeCaches($hasProjects, $hasPeriods);

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestDataRow = (int) $sheet->getHighestDataRow();
        $highestDataColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headers = $this->extractHeaders($sheet, $highestDataColumnIndex);
        $coreCols = $this->detectCoreColumns($headers);
        $metricColumns = $this->detectMetricColumns($headers);

        if ($metricColumns === []) {
            throw new RuntimeException('No plan/actual indicator columns were detected in the uploaded file.');
        }

        $summary = [
            'sheet' => (string) $sheet->getTitle(),
            'rows_total' => max(0, $highestDataRow - 1),
            'rows_processed' => 0,
            'rows_skipped' => 0,
            'projects_created' => 0,
            'periods_created' => 0,
            'metrics_detected' => count($metricColumns),
            'indicators_created' => 0,
            'targets_upserted' => 0,
            'reports_upserted' => 0,
            'warnings' => [],
            'indicator_samples' => [],
            'generated_at' => now()->toDateTimeString(),
        ];

        DB::connection()->disableQueryLog();

        DB::transaction(function () use (
            $sheet,
            $coreCols,
            $highestDataRow,
            $metricColumns,
            $hasProjects,
            $hasPeriods,
            &$summary
        ): void {
            for ($row = 2; $row <= $highestDataRow; $row++) {
                $timestampRaw = $this->stringValue($sheet->getCell([$coreCols['timestamp'], $row])->getFormattedValue());
                $projectNameRaw = $this->stringValue($sheet->getCell([$coreCols['project_name'], $row])->getFormattedValue());
                $projectCodeRaw = $this->stringValue($sheet->getCell([$coreCols['project_code'], $row])->getFormattedValue());
                $periodRaw = $this->stringValue($sheet->getCell([$coreCols['period'], $row])->getFormattedValue());
                $commentRaw = $coreCols['comment'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['comment'], $row])->getFormattedValue())
                    : null;

                if ($this->isRowEmpty([$timestampRaw, $projectNameRaw, $projectCodeRaw, $periodRaw])) {
                    $summary['rows_skipped']++;

                    continue;
                }

                if ($this->looksLikeHeaderNoise($timestampRaw, $projectCodeRaw, $projectNameRaw)) {
                    $summary['rows_skipped']++;
                    $this->addWarning($summary, "Row {$row}: skipped header/noise row.");

                    continue;
                }

                $period = $this->parsePeriodRange($periodRaw, $timestampRaw);

                if ($period === null) {
                    $summary['rows_skipped']++;
                    $this->addWarning($summary, "Row {$row}: failed to parse reporting period '{$periodRaw}'.");

                    continue;
                }

                [$projectId, $projectCodeDisplay, $projectCreated] = $this->resolveProject($projectNameRaw, $projectCodeRaw, $hasProjects);

                if ($projectCreated) {
                    $summary['projects_created']++;
                }

                $periodId = null;
                if ($hasPeriods && $projectId !== null) {
                    [$periodId, $periodCreated] = $this->resolvePeriod($projectId, $period['start'], $period['end'], $period['label']);
                    if ($periodCreated) {
                        $summary['periods_created']++;
                    }
                }

                $rowHadData = false;

                foreach ($metricColumns as $metric) {
                    $planRaw = $metric['plan_col'] !== null ? $sheet->getCell([$metric['plan_col'], $row])->getFormattedValue() : null;
                    $actualRaw = $metric['actual_col'] !== null ? $sheet->getCell([$metric['actual_col'], $row])->getFormattedValue() : null;

                    $planValue = $this->numericValue($planRaw);
                    $actualValue = $this->numericValue($actualRaw);

                    if (($planValue === null) && ($actualValue === null)) {
                        continue;
                    }

                    $rowHadData = true;

                    [$indicatorId, $indicatorCreated] = $this->resolveIndicator(
                        $metric['label'],
                        $metric['key'],
                        $projectId,
                        $projectCodeDisplay
                    );

                    if ($indicatorCreated) {
                        $summary['indicators_created']++;
                    }

                    if ($planValue !== null) {
                        $this->upsertTarget(
                            $indicatorId,
                            $periodId,
                            $period['start'],
                            $period['end'],
                            $projectCodeDisplay,
                            $planValue,
                            $commentRaw
                        );
                        $summary['targets_upserted']++;
                    }

                    if ($actualValue !== null) {
                        $this->upsertReport(
                            $indicatorId,
                            $periodId,
                            $period['start'],
                            $period['end'],
                            $projectCodeDisplay,
                            $actualValue,
                            $commentRaw
                        );
                        $summary['reports_upserted']++;
                    }
                }

                if ($rowHadData) {
                    $summary['rows_processed']++;
                } else {
                    $summary['rows_skipped']++;
                    $this->addWarning($summary, "Row {$row}: no numeric plan/actual values found.");
                }
            }
        });

        $summary['indicator_samples'] = array_slice($this->indicatorCreated, 0, 20);
        $summary['debug_report'] = $this->storeDebugReport($summary);

        return $summary;
    }

    private function primeCaches(bool $hasProjects, bool $hasPeriods): void
    {
        $this->reportColumns = array_flip(Schema::getColumnListing('me_indicator_reports'));
        $this->targetColumns = array_flip(Schema::getColumnListing('me_indicator_targets'));
        $this->indicatorColumns = array_flip(Schema::getColumnListing('me_indicators'));

        if ($hasProjects) {
            $this->projectColumns = array_flip(Schema::getColumnListing('me_projects'));

            $projects = DB::table('me_projects')->select('id', 'project_code', 'name')->get();
            foreach ($projects as $project) {
                $codeKey = $this->normalizeProjectCode((string) ($project->project_code ?? ''));
                $nameKey = $this->normalizeProjectNameKey((string) ($project->name ?? ''));

                if ($codeKey !== '') {
                    $this->projectCodeCache[$codeKey] = (int) $project->id;
                }
                if ($nameKey !== '') {
                    $this->projectNameCache[$nameKey] = (int) $project->id;
                }
            }
        }

        if ($hasPeriods) {
            $this->periodColumns = array_flip(Schema::getColumnListing('me_reporting_periods'));
        }

        $indicators = DB::table('me_indicators')->select('id', 'code', 'project_id', 'name', 'name_local')->get();
        foreach ($indicators as $indicator) {
            $id = (int) $indicator->id;
            $code = (string) ($indicator->code ?? '');
            if ($code !== '') {
                $this->indicatorCodeCache[$code] = $id;
            }

            $projectId = (int) ($indicator->project_id ?? 0);
            $label = (string) ($indicator->name_local ?: $indicator->name);
            $labelKey = $this->indicatorLabelKey($projectId, $label);
            if ($labelKey !== '') {
                $this->indicatorLabelCache[$labelKey] = $id;
            }
        }
    }

    private function extractHeaders($sheet, int $highestDataColumnIndex): array
    {
        $headers = [];
        for ($col = 1; $col <= $highestDataColumnIndex; $col++) {
            $headers[$col] = $this->stringValue($sheet->getCell([$col, 1])->getFormattedValue());
        }

        return $headers;
    }

    private function detectCoreColumns(array $headers): array
    {
        $timestampAliases = [
            'timestamp',
            'submission time',
            'submitted at',
        ];

        $projectNameAliases = [
            'project name',
            'project title',
            'ፕሮጀክቱ ስም',
            'á•áˆ®áŒ€áŠ­á‰± áˆµáˆ',
        ];

        $projectCodeAliases = [
            'project code',
            'project number',
            'project no',
            'project #',
            'ፕሮጀክቱ ቁጥር',
            'á•áˆ®áŒ€áŠ­á‰± á‰áŒ¥áˆ­',
        ];

        $periodAliases = [
            'reporting period covered',
            'reporting period',
            'period covered',
            'period',
            'ሪፖርቱ',
            'áˆªá–áˆ­á‰±',
        ];

        $commentAliases = [
            'additional comment',
            'comment',
            'remarks',
            'notes',
            'አስተያየት',
            'áŠ áˆµá‰°á‹«á‹¨á‰µ',
        ];

        $timestampCol = null;
        $projectNameCol = null;
        $projectCodeCol = null;
        $periodCol = null;
        $commentCol = null;

        foreach ($headers as $col => $header) {
            $normalized = mb_strtolower($this->cleanHeader($header));

            if (($timestampCol === null) && $this->containsAnyNormalized($normalized, $timestampAliases)) {
                $timestampCol = $col;
            }

            if (($projectNameCol === null) && $this->containsAnyNormalized($normalized, $projectNameAliases)) {
                $projectNameCol = $col;
            }

            if (($projectCodeCol === null) && $this->containsAnyNormalized($normalized, $projectCodeAliases)) {
                $projectCodeCol = $col;
            }

            if (($periodCol === null) && $this->containsAnyNormalized($normalized, $periodAliases)) {
                $periodCol = $col;
            }

            if (($commentCol === null) && $this->containsAnyNormalized($normalized, $commentAliases)) {
                $commentCol = $col;
            }
        }

        if (($projectNameCol === null) || ($projectCodeCol === null) || ($periodCol === null)) {
            throw new RuntimeException('Missing required columns. Ensure project name, project code/project number, and reporting period columns exist.');
        }

        return [
            'timestamp' => $timestampCol ?? 1,
            'project_name' => $projectNameCol,
            'project_code' => $projectCodeCol,
            'period' => $periodCol,
            'comment' => $commentCol,
        ];
    }

    private function detectMetricColumns(array $headers): array
    {
        $metrics = [];

        foreach ($headers as $col => $headerRaw) {
            $header = $this->cleanHeader($headerRaw);
            if ($header === '') {
                continue;
            }

            $kind = $this->detectMetricKind($header);
            if ($kind === null) {
                continue;
            }

            $label = $this->metricLabelFromHeader($header);
            $key = $this->normalizeText($label);

            if ($key === '') {
                continue;
            }

            if (! isset($metrics[$key])) {
                $metrics[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'plan_col' => null,
                    'actual_col' => null,
                ];
            }

            $metrics[$key]["{$kind}_col"] = $col;
        }

        return array_values($metrics);
    }

    private function detectMetricKind(string $header): ?string
    {
        $headerLower = mb_strtolower($header);

        foreach (self::ACTUAL_KEYWORDS as $keyword) {
            if (str_contains($headerLower, mb_strtolower($keyword))) {
                return 'actual';
            }
        }

        foreach (self::PLAN_KEYWORDS as $keyword) {
            if (str_contains($headerLower, mb_strtolower($keyword))) {
                return 'plan';
            }
        }

        return null;
    }

    private function metricLabelFromHeader(string $header): string
    {
        $label = preg_replace('/^\s*\d+\.\s*/u', '', $header);
        $label = (string) $label;

        $keywords = array_merge(self::PLAN_KEYWORDS, self::ACTUAL_KEYWORDS, ['áˆ˜áŒ áŠ•']);
        usort($keywords, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($keywords as $keyword) {
            $label = str_ireplace($keyword, '', $label);
        }

        $label = preg_replace('/\s+/u', ' ', (string) $label);
        $label = trim((string) $label, " \t\n\r\0\x0B-:,.ØŒ");

        return $label !== '' ? $label : $header;
    }
    private function resolveProject(string $projectNameRaw, string $projectCodeRaw, bool $hasProjects): array
    {
        $projectName = $projectNameRaw !== '' ? $projectNameRaw : 'Unnamed Project';
        $resolvedCode = $this->resolveProjectCode($projectCodeRaw, $projectName);
        $projectCodeCanonical = $this->normalizeProjectCode($resolvedCode);
        $projectCodeDisplay = $this->formatProjectCode($projectCodeCanonical !== '' ? $projectCodeCanonical : $resolvedCode);

        if (! $hasProjects) {
            return [null, $projectCodeDisplay, false];
        }

        $nameKey = $this->normalizeProjectNameKey($projectName);
        if (($projectCodeCanonical !== '') && isset($this->projectCodeCache[$projectCodeCanonical])) {
            return [$this->projectCodeCache[$projectCodeCanonical], $projectCodeDisplay, false];
        }

        if (($nameKey !== '') && isset($this->projectNameCache[$nameKey])) {
            return [$this->projectNameCache[$nameKey], $projectCodeDisplay, false];
        }

        $insert = [
            'name' => $projectName,
            'project_code' => $projectCodeDisplay !== '' ? $projectCodeDisplay : 'PROJECT-' . strtoupper(substr(md5($projectName), 0, 8)),
        ];

        if (isset($this->projectColumns['description'])) {
            $insert['description'] = null;
        }
        if (isset($this->projectColumns['created_at'])) {
            $insert['created_at'] = now();
        }
        if (isset($this->projectColumns['updated_at'])) {
            $insert['updated_at'] = now();
        }

        $existingId = DB::table('me_projects')
            ->where('project_code', $insert['project_code'])
            ->value('id');

        if ($existingId !== null) {
            $projectId = (int) $existingId;
        } else {
            try {
                $projectId = (int) DB::table('me_projects')->insertGetId($insert);
            } catch (Throwable) {
                $projectId = (int) (DB::table('me_projects')
                    ->where('project_code', $insert['project_code'])
                    ->value('id') ?? 0);

                if ($projectId === 0) {
                    throw new RuntimeException("Failed to create or resolve project with code '{$insert['project_code']}'.");
                }
            }
        }

        if ($projectCodeCanonical !== '') {
            $this->projectCodeCache[$projectCodeCanonical] = $projectId;
        }
        if ($nameKey !== '') {
            $this->projectNameCache[$nameKey] = $projectId;
        }

        return [$projectId, $insert['project_code'], true];
    }

    private function resolvePeriod(int $projectId, string $startDate, string $endDate, string $label): array
    {
        $key = "{$projectId}|{$startDate}|{$endDate}";
        if (isset($this->periodCache[$key])) {
            return [$this->periodCache[$key], false];
        }

        $existingId = DB::table('me_reporting_periods')
            ->where('project_id', $projectId)
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->where('type', 'weekly')
            ->value('id');

        if ($existingId !== null) {
            $this->periodCache[$key] = (int) $existingId;

            return [(int) $existingId, false];
        }

        $insert = [
            'project_id' => $projectId,
            'type' => 'weekly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label !== '' ? $label : "Week {$startDate} - {$endDate}",
            'is_locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = (int) DB::table('me_reporting_periods')->insertGetId($insert);
        $this->periodCache[$key] = $id;

        return [$id, true];
    }

    private function resolveIndicator(string $metricLabel, string $metricKey, ?int $projectId, string $projectCode): array
    {
        $projectKey = $projectId ?? 0;
        $labelKey = $this->indicatorLabelKey($projectKey, $metricLabel);

        if (($labelKey !== '') && isset($this->indicatorLabelCache[$labelKey])) {
            return [$this->indicatorLabelCache[$labelKey], false];
        }

        [$dataType, $unit] = $this->inferIndicatorType($metricLabel);
        $codeBase = $this->generateIndicatorCode($projectCode, $metricLabel, $metricKey);
        $code = $codeBase;
        $suffix = 2;

        while (isset($this->indicatorCodeCache[$code])) {
            $code = substr($codeBase, 0, 94) . '_' . $suffix;
            $suffix++;
        }

        $insert = [
            'code' => $code,
            'name' => $metricLabel,
            'framework_type' => 'output',
            'unit' => $unit,
            'frequency' => 'weekly',
            'description' => null,
            'is_active' => true,
            'disaggregation_required' => false,
            'threshold_warning' => 70,
            'threshold_critical' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (isset($this->indicatorColumns['project_id'])) {
            $insert['project_id'] = $projectId;
        }
        if (isset($this->indicatorColumns['name_local'])) {
            $insert['name_local'] = $metricLabel;
        }
        if (isset($this->indicatorColumns['direction'])) {
            $insert['direction'] = 'higher_is_better';
        }
        if (isset($this->indicatorColumns['data_type'])) {
            $insert['data_type'] = $dataType;
        }

        $indicatorId = (int) DB::table('me_indicators')->insertGetId($insert);

        $this->indicatorCodeCache[$code] = $indicatorId;
        if ($labelKey !== '') {
            $this->indicatorLabelCache[$labelKey] = $indicatorId;
        }

        $this->indicatorCreated[] = [
            'code' => $code,
            'name' => $metricLabel,
            'project_code' => $projectCode,
        ];

        return [$indicatorId, true];
    }

    private function upsertTarget(
        int $indicatorId,
        ?int $periodId,
        string $startDate,
        string $endDate,
        string $projectCode,
        float $targetValue,
        ?string $notes
    ): void {
        $match = [
            'indicator_id' => $indicatorId,
            'period_start' => $startDate,
            'period_end' => $endDate,
        ];

        if (isset($this->targetColumns['scope_project'])) {
            $match['scope_project'] = $projectCode !== '' ? $projectCode : null;
        }

        $values = [
            'target_value' => round($targetValue, 2),
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (isset($this->targetColumns['reporting_period_id'])) {
            $values['reporting_period_id'] = $periodId;
        }
        if (isset($this->targetColumns['notes'])) {
            $values['notes'] = $notes;
        }

        DB::table('me_indicator_targets')->updateOrInsert($match, $values);
    }

    private function upsertReport(
        int $indicatorId,
        ?int $periodId,
        string $startDate,
        string $endDate,
        string $projectCode,
        float $actualValue,
        ?string $comment
    ): void {
        $match = [
            'indicator_id' => $indicatorId,
            'period_start' => $startDate,
            'period_end' => $endDate,
        ];

        if (isset($this->reportColumns['scope_project'])) {
            $match['scope_project'] = $projectCode !== '' ? $projectCode : null;
        }

        $values = [
            'actual_value' => round($actualValue, 2),
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (isset($this->reportColumns['reporting_period_id'])) {
            $values['reporting_period_id'] = $periodId;
        }
        if (isset($this->reportColumns['source'])) {
            $values['source'] = 'import';
        }
        if (isset($this->reportColumns['entered_by'])) {
            $values['entered_by'] = Auth::id();
        }
        if (isset($this->reportColumns['entered_at'])) {
            $values['entered_at'] = now();
        }
        if (isset($this->reportColumns['comment'])) {
            $values['comment'] = $comment;
        }
        if (isset($this->reportColumns['notes'])) {
            $values['notes'] = $comment;
        }

        DB::table('me_indicator_reports')->updateOrInsert($match, $values);
    }

    private function parsePeriodRange(?string $periodRaw, ?string $timestampRaw): ?array
    {
        $periodRaw = $this->stringValue($periodRaw);
        $timestampRaw = $this->stringValue($timestampRaw);

        $dates = [];
        if ($periodRaw !== '') {
            if (preg_match_all('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b/u', $periodRaw, $matches)) {
                foreach ($matches[0] as $token) {
                    $parsed = $this->parseDateToken($token);
                    if ($parsed !== null) {
                        $dates[] = $parsed;
                    }
                }
            }

            if ($dates === []) {
                $monthRange = '/(january|february|march|april|may|june|july|august|september|october|november|december)[,\s]*(\d{1,2})\s*(?:-|to)\s*(\d{1,2})[,\/\s-]*(\d{4})/iu';
                if (preg_match($monthRange, $periodRaw, $m) === 1) {
                    $start = Carbon::parse("{$m[1]} {$m[2]} {$m[4]}");
                    $end = Carbon::parse("{$m[1]} {$m[3]} {$m[4]}");
                    $dates = [$start, $end];
                }
            }
        }

        if (count($dates) >= 2) {
            usort($dates, fn (Carbon $a, Carbon $b): int => $a->lessThan($b) ? -1 : 1);

            return [
                'start' => $dates[0]->toDateString(),
                'end' => $dates[count($dates) - 1]->toDateString(),
                'label' => $periodRaw,
            ];
        }

        $timestampDate = $this->parseDateToken($timestampRaw);
        if ($timestampDate !== null) {
            return [
                'start' => $timestampDate->copy()->subDays(6)->toDateString(),
                'end' => $timestampDate->toDateString(),
                'label' => $periodRaw !== '' ? $periodRaw : "Week ending {$timestampDate->toDateString()}",
            ];
        }

        return null;
    }

    private function parseDateToken(?string $raw): ?Carbon
    {
        $raw = $this->stringValue($raw);
        if ($raw === '') {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', $raw);
        $value = trim((string) $value);
        $value = preg_replace('/(\d{1,2})\/(\d{2})(\d{4})/', '$1/$2/$3', (string) $value);

        $formats = [
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
            'd/m/y',
            'm/d/y',
            'Y-m-d',
            'n/j/Y',
            'j/n/Y',
            'n/j/y',
            'j/n/y',
            'm/d/Y H:i:s',
            'n/j/Y H:i:s',
            'd/m/Y H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function inferIndicatorType(string $label): array
    {
        $moneyHints = ['ገቢ', 'ወጪ', 'ብድር', 'ቁጠባ', 'ትርፍ', 'ETB', 'birr'];
        foreach ($moneyHints as $hint) {
            if (str_contains(mb_strtolower($label), mb_strtolower($hint))) {
                return ['currency', 'ETB'];
            }
        }

        return ['integer', 'count'];
    }

    private function generateIndicatorCode(string $projectCode, string $label, string $metricKey): string
    {
        $projectPrefix = $this->normalizeProjectCode($projectCode);
        if ($projectPrefix === '') {
            $projectPrefix = 'PRJ';
        }

        $latin = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
        $latin = is_string($latin) ? $latin : '';
        $token = strtoupper((string) preg_replace('/[^A-Z0-9]+/', '_', $latin));
        $token = trim($token, '_');

        if ($token === '') {
            $token = 'IND_' . strtoupper(substr(md5($metricKey), 0, 8));
        }

        return substr("{$projectPrefix}_{$token}", 0, 100);
    }

    private function resolveProjectCode(string $projectCodeRaw, string $projectNameRaw): string
    {
        $raw = $this->stringValue($projectCodeRaw);
        $name = $this->stringValue($projectNameRaw);

        if ($raw !== '') {
            $normalized = $this->normalizeProjectCode($raw);
            if ($normalized !== '') {
                return $this->canonicalizeProjectCode($normalized);
            }
        }

        if (preg_match('/SVO\\s*ET\\s*-?\\s*\\d+/iu', $name, $match) === 1) {
            return $this->canonicalizeProjectCode($this->normalizeProjectCode($match[0]));
        }

        if (preg_match('/SVOET\\s*-?\\s*\\d+/iu', $name, $match) === 1) {
            return $this->canonicalizeProjectCode($this->normalizeProjectCode($match[0]));
        }

        return $this->canonicalizeProjectCode($this->normalizeProjectCode($name));
    }

    private function normalizeProjectCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = (string) preg_replace('/[^A-Z0-9]+/', '', $code);

        return $code;
    }

    private function canonicalizeProjectCode(string $normalizedCode): string
    {
        if ($normalizedCode === '') {
            return '';
        }

        if (preg_match('/^SVOET(\d+)$/', $normalizedCode, $match) === 1) {
            return 'SVOET' . str_pad($match[1], 3, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^ET(\d+)$/', $normalizedCode, $match) === 1) {
            return 'SVOET' . str_pad($match[1], 3, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^\d+$/', $normalizedCode) === 1) {
            return 'SVOET' . str_pad($normalizedCode, 3, '0', STR_PAD_LEFT);
        }

        return $normalizedCode;
    }

    private function formatProjectCode(string $code): string
    {
        if ($code === '') {
            return '';
        }

        if (preg_match('/^SVOET(\d+)$/', $code, $match) === 1) {
            return 'SVOET-' . str_pad($match[1], 3, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^\d+$/', $code) === 1) {
            return str_pad($code, 3, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    private function numericValue(mixed $value): ?float
    {
        $string = $this->stringValue($value);

        if (($string === '') || in_array($string, ['-', '--', '—', '–'], true)) {
            return null;
        }

        $string = str_replace([',', ' '], '', $string);
        if (($string === '') || ! is_numeric($string)) {
            return null;
        }

        return (float) $string;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function cleanHeader(string $header): string
    {
        $header = trim($header);
        $header = preg_replace('/\s+/u', ' ', $header);

        return trim((string) $header);
    }

    private function containsAnyNormalized(string $normalizedHeader, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            $needle = mb_strtolower(trim((string) $alias));
            if (($needle !== '') && str_contains($normalizedHeader, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/^\s*\d+\.\s*/u', '', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $value);

        return (string) $value;
    }

    private function normalizeProjectNameKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\([^)]*\)/u', ' ', (string) $value);
        $value = preg_replace('/svo\s*et\s*-?\s*\d+/iu', ' ', (string) $value);
        $value = str_replace(['project', 'ፕሮጀክት', 'svo'], ' ', (string) $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $value);

        return (string) $value;
    }

    private function indicatorLabelKey(int $projectId, string $label): string
    {
        $normalized = $this->normalizeText($label);
        if ($normalized === '') {
            return '';
        }

        return $projectId . '|' . $normalized;
    }

    private function isRowEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->stringValue($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function looksLikeHeaderNoise(string $timestamp, string $projectCode, string $projectName): bool
    {
        $payload = mb_strtolower($timestamp . ' ' . $projectCode . ' ' . $projectName);

        return str_contains($payload, 'ፕሮጀክቱ ቁጥር')
            || str_contains($payload, 'timestamp')
            || str_contains($payload, 'column');
    }

    private function addWarning(array &$summary, string $warning): void
    {
        if (count($summary['warnings']) < 100) {
            $summary['warnings'][] = $warning;
        }
    }

    private function storeDebugReport(array $summary): ?string
    {
        try {
            $dir = 'me-import-reports';
            $filename = 'weekly-import-' . now()->format('Ymd_His') . '.json';
            $path = $dir . '/' . $filename;

            Storage::disk('local')->put($path, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return storage_path('app/' . $path);
        } catch (Throwable) {
            return null;
        }
    }
}

