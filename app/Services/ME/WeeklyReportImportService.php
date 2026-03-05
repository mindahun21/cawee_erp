<?php

namespace App\Services\ME;

use App\Models\ME\MeIndicatorReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class WeeklyReportImportService
{
    private const DECIMAL_14_2_MIN = -999999999999.99;
    private const DECIMAL_14_2_MAX = 999999999999.99;
    private const IMPORT_HISTORY_PATH = 'me-import-reports/weekly-import-history.json';
    private const IMPORT_HISTORY_LIMIT = 500;

    private const PLAN_KEYWORDS = [
        'á‹•á‰…á‹µ',
        'á‹¨á‰³á‰€á‹°',
        'plan',
        'planned',
        'target',
    ];

    private const ACTUAL_KEYWORDS = [
        'áŠ­áŠ•á‹áŠ•',
        'á‹¨á‰°áŒˆáŠ˜',
        'á‹¨á‰°áˆ°áŒ ',
        'á‹¨á‰°áˆ˜áˆˆáˆ°',
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

        $fileHash = hash_file('sha256', $filePath) ?: null;
        $alreadyImportedAt = null;
        if (is_string($fileHash) && ($fileHash !== '')) {
            $historyEntry = $this->findImportHistoryEntry($fileHash);
            if ($historyEntry !== null) {
                $alreadyImportedAt = (string) ($historyEntry['imported_at'] ?? '');
            }
        }

        if (is_string($alreadyImportedAt) && ($alreadyImportedAt !== '')) {
            throw new RuntimeException(
                "This exact file was already imported on {$alreadyImportedAt}. Duplicate file import is blocked."
            );
        }

        $this->primeCaches($hasProjects, $hasPeriods);

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestDataRow = (int) $sheet->getHighestDataRow();
        $highestDataColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headers = $this->extractHeaders($sheet, $highestDataColumnIndex);
        $strictColumns = $this->detectStrictLongFormatColumns($headers);
        if ($strictColumns !== null) {
            return $this->importStrictLongFormat(
                $sheet,
                $highestDataRow,
                $strictColumns,
                $hasProjects,
                $hasPeriods,
                $fileHash,
                $filePath,
                $alreadyImportedAt
            );
        }

        throw new RuntimeException(
            'Unsupported import template. Use fixed columns: '
            . 'project_code, project_name, project_start_date, project_end_date, '
            . 'indicator_name, framework_type, frequency, unit, '
            . 'indicator_threshold_warning, indicator_threshold_critical, '
            . 'period_start, period_end, target_name, planned_value, actual_value, report_time, report_description. '
            . 'Do not use dynamic columns like "Women Amount Planned" or "Women Amount Actual".'
        );

        $coreCols = $this->detectCoreColumns($headers);
        $metricColumns = $this->detectMetricColumns($headers);

        if (! ((bool) ($coreCols['schema_mode'] ?? false))) {
            throw new RuntimeException('Missing required bulk schema columns. Required: project_code, project_name, project_start_date, project_end_date, indicator_name, framework_type, frequency, unit, indicator_threshold_warning, indicator_threshold_critical, period_start, period_end, target_name, planned_value, actual_value (column required; value optional per row).');
        }

        if ($metricColumns === []) {
            throw new RuntimeException('No plan/actual indicator columns were detected in the uploaded file.');
        }

        $summary = [
            'sheet' => (string) $sheet->getTitle(),
            'rows_total' => max(0, $highestDataRow - 1),
            'rows_processed' => 0,
            'rows_skipped' => 0,
            'rows_failed' => 0,
            'projects_created' => 0,
            'periods_created' => 0,
            'metrics_detected' => count($metricColumns),
            'indicators_created' => 0,
            'targets_upserted' => 0,
            'targets_created' => 0,
            'targets_updated' => 0,
            'targets_unchanged' => 0,
            'reports_upserted' => 0,
            'reports_created' => 0,
            'reports_updated' => 0,
            'reports_unchanged' => 0,
            'duplicate_rows_in_file' => 0,
            'duplicate_target_rows_in_file' => 0,
            'duplicate_report_rows_in_file' => 0,
            'warnings' => [],
            'rejected_rows' => [],
            'accepted_rows_preview' => [],
            'rejected_rows_csv' => null,
            'indicator_samples' => [],
            'generated_at' => now()->toDateTimeString(),
            'file_hash' => $fileHash,
            'already_imported_at' => $alreadyImportedAt,
        ];

        if (is_string($alreadyImportedAt) && ($alreadyImportedAt !== '')) {
            $summary['warnings'][] = "This file hash was already imported on {$alreadyImportedAt}. Import continued and only changed rows were written.";
        }

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
            $seenProjectPeriodRows = [];
            $seenTargetKeys = [];
            $seenReportKeys = [];
            $schemaMode = (bool) ($coreCols['schema_mode'] ?? false);
            $allowedFrameworks = ['output', 'outcome', 'impact'];
            $allowedFrequencies = ['weekly', 'monthly', 'quarterly', 'semiannual', 'annual'];

            for ($row = 2; $row <= $highestDataRow; $row++) {
                $timestampRaw = $this->stringValue($sheet->getCell([$coreCols['timestamp'], $row])->getFormattedValue());
                $projectNameRaw = $this->stringValue($sheet->getCell([$coreCols['project_name'], $row])->getFormattedValue());
                $projectCodeRaw = $this->stringValue($sheet->getCell([$coreCols['project_code'], $row])->getFormattedValue());
                $periodRaw = $coreCols['period'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['period'], $row])->getFormattedValue())
                    : '';
                $commentRaw = $coreCols['comment'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['comment'], $row])->getFormattedValue())
                    : null;
                $projectStartRaw = $coreCols['project_start_date'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['project_start_date'], $row])->getFormattedValue())
                    : '';
                $projectEndRaw = $coreCols['project_end_date'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['project_end_date'], $row])->getFormattedValue())
                    : '';
                $periodStartRaw = $coreCols['period_start'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['period_start'], $row])->getFormattedValue())
                    : '';
                $periodEndRaw = $coreCols['period_end'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['period_end'], $row])->getFormattedValue())
                    : '';
                $frameworkTypeRaw = $coreCols['framework_type'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['framework_type'], $row])->getFormattedValue())
                    : '';
                $frequencyRaw = $coreCols['frequency'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['frequency'], $row])->getFormattedValue())
                    : '';
                $unitRaw = $coreCols['unit'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['unit'], $row])->getFormattedValue())
                    : '';
                $indicatorNameRaw = $coreCols['indicator_name'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['indicator_name'], $row])->getFormattedValue())
                    : '';
                $indicatorThresholdWarningRaw = $coreCols['indicator_threshold_warning'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['indicator_threshold_warning'], $row])->getFormattedValue())
                    : '';
                $indicatorThresholdCriticalRaw = $coreCols['indicator_threshold_critical'] !== null
                    ? $this->stringValue($sheet->getCell([$coreCols['indicator_threshold_critical'], $row])->getFormattedValue())
                    : '';
                $periodDisplay = $periodRaw !== '' ? $periodRaw : trim($periodStartRaw . ' to ' . $periodEndRaw);

                if ($this->isRowEmpty([
                    $timestampRaw,
                    $projectNameRaw,
                    $projectCodeRaw,
                    $periodRaw,
                    $periodStartRaw,
                    $periodEndRaw,
                    $projectStartRaw,
                    $projectEndRaw,
                    $frameworkTypeRaw,
                    $frequencyRaw,
                    $unitRaw,
                    $indicatorNameRaw,
                    $indicatorThresholdWarningRaw,
                    $indicatorThresholdCriticalRaw,
                ])) {
                    $summary['rows_skipped']++;

                    continue;
                }

                if ($this->looksLikeHeaderNoise($timestampRaw, $projectCodeRaw, $projectNameRaw)) {
                    $summary['rows_skipped']++;
                    $this->addWarning($summary, "Row {$row}: skipped header/noise row.");

                    continue;
                }

                try {
                    $period = null;
                    $projectStart = null;
                    $projectEnd = null;
                    $frameworkType = null;
                    $frequency = null;
                    $unit = null;

                    if ($schemaMode) {
                        $requiredInSchemaMode = [
                            'project_start_date' => $projectStartRaw,
                            'project_end_date' => $projectEndRaw,
                            'period_start' => $periodStartRaw,
                            'period_end' => $periodEndRaw,
                            'framework_type' => $frameworkTypeRaw,
                            'frequency' => $frequencyRaw,
                            'unit' => $unitRaw,
                            'indicator_name' => $indicatorNameRaw,
                            'indicator_threshold_warning' => $indicatorThresholdWarningRaw,
                            'indicator_threshold_critical' => $indicatorThresholdCriticalRaw,
                        ];

                        $missing = [];
                        foreach ($requiredInSchemaMode as $key => $value) {
                            if ($this->stringValue($value) === '') {
                                $missing[] = $key;
                            }
                        }

                        if ($missing !== []) {
                            $summary['rows_skipped']++;
                            $reason = 'Missing required schema column value(s): ' . implode(', ', $missing) . '.';
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                            continue;
                        }

                        $projectStart = $this->parseDateToken($projectStartRaw);
                        $projectEnd = $this->parseDateToken($projectEndRaw);
                        $periodStart = $this->parseDateToken($periodStartRaw);
                        $periodEnd = $this->parseDateToken($periodEndRaw);

                        if (($projectStart === null) || ($projectEnd === null)) {
                            $summary['rows_skipped']++;
                            $reason = 'Invalid project_start_date or project_end_date format.';
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                            continue;
                        }

                        if (($periodStart === null) || ($periodEnd === null)) {
                            $summary['rows_skipped']++;
                            $reason = 'Invalid period_start or period_end format.';
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                            continue;
                        }

                        if ($projectEnd->lt($projectStart)) {
                            $summary['rows_skipped']++;
                            $reason = 'project_end_date must be on or after project_start_date.';
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                            continue;
                        }

                        if ($periodEnd->lt($periodStart)) {
                            $summary['rows_skipped']++;
                            $reason = 'period_end must be on or after period_start.';
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                            continue;
                        }

                        $frameworkType = mb_strtolower(trim($frameworkTypeRaw));
                        $frequency = mb_strtolower(trim($frequencyRaw));
                        $unit = $this->stringValue($unitRaw);

                        if (! in_array($frameworkType, $allowedFrameworks, true)) {
                            $summary['rows_skipped']++;
                            $reason = "Invalid framework_type '{$frameworkTypeRaw}'. Allowed: output, outcome, impact.";
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                            continue;
                        }

                        if (! in_array($frequency, $allowedFrequencies, true)) {
                            $summary['rows_skipped']++;
                            $reason = "Invalid frequency '{$frequencyRaw}'. Allowed: weekly, monthly, quarterly, semiannual, annual.";
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                            continue;
                        }

                        $period = [
                            'start' => $periodStart->toDateString(),
                            'end' => $periodEnd->toDateString(),
                            'label' => $periodDisplay !== '' ? $periodDisplay : ($periodStart->toDateString() . ' to ' . $periodEnd->toDateString()),
                        ];
                    } else {
                        $period = $this->parsePeriodRange($periodRaw, $timestampRaw);
                    }

                    if ($period === null) {
                        $summary['rows_skipped']++;
                        $this->addWarning($summary, "Row {$row}: failed to parse reporting period '{$periodRaw}'.");
                        $this->addRejectedRow($summary, $row, "Failed to parse reporting period '{$periodRaw}'.", $projectNameRaw, $projectCodeRaw, $periodDisplay);

                        continue;
                    }

                    [$projectId, $projectCodeDisplay, $projectCreated] = $this->resolveProject(
                        $projectNameRaw,
                        $projectCodeRaw,
                        $hasProjects,
                        $projectStart?->toDateString(),
                        $projectEnd?->toDateString(),
                        $schemaMode
                    );

                    if ($projectCreated) {
                        $summary['projects_created']++;
                    }

                    if ($this->stringValue($indicatorNameRaw) === '') {
                        $summary['rows_skipped']++;
                        $reason = 'indicator_name is required.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                        continue;
                    }

                    $indicatorThresholdWarning = $this->numericValue($indicatorThresholdWarningRaw);
                    $indicatorThresholdCritical = $this->numericValue($indicatorThresholdCriticalRaw);
                    if (($indicatorThresholdWarning === null) || ($indicatorThresholdCritical === null)) {
                        $summary['rows_skipped']++;
                        $reason = 'indicator_threshold_warning and indicator_threshold_critical must be numeric.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                        continue;
                    }

                    if (($indicatorThresholdWarning < 0) || ($indicatorThresholdWarning > 100) || ($indicatorThresholdCritical < 0) || ($indicatorThresholdCritical > 100)) {
                        $summary['rows_skipped']++;
                        $reason = 'Indicator thresholds must be within 0 to 100.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                        continue;
                    }

                    if ($indicatorThresholdCritical > $indicatorThresholdWarning) {
                        $summary['rows_skipped']++;
                        $reason = 'indicator_threshold_critical cannot be greater than indicator_threshold_warning.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                        continue;
                    }

                    $projectPeriodKey = $this->buildProjectIndicatorPeriodKey(
                        $projectCodeDisplay !== '' ? $projectCodeDisplay : $projectCodeRaw,
                        $indicatorNameRaw,
                        $period['start'],
                        $period['end']
                    );

                    if (isset($seenProjectPeriodRows[$projectPeriodKey])) {
                        $summary['rows_skipped']++;
                        $summary['duplicate_rows_in_file']++;

                        $firstRow = $seenProjectPeriodRows[$projectPeriodKey];
                        $reason = "Duplicate project-indicator-week row (first seen on row {$firstRow}) for indicator '{$indicatorNameRaw}' and period {$period['start']} to {$period['end']}. Entire duplicate row was ignored.";
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);

                        continue;
                    }

                    $seenProjectPeriodRows[$projectPeriodKey] = $row;

                    $periodId = null;
                    if ($hasPeriods && $projectId !== null) {
                        $periodType = ($schemaMode && is_string($frequency) && ($frequency !== '')) ? $frequency : 'weekly';
                        [$periodId, $periodCreated] = $this->resolvePeriod($projectId, $period['start'], $period['end'], $period['label'], $periodType);
                        if ($periodCreated) {
                            $summary['periods_created']++;
                        }
                    }

                    $rowHadData = false;
                    $rowHadNumericInput = false;
                    $rowHadRejectedMetrics = false;
                    $rowHadUnchangedMetrics = false;
                    $metricWrites = 0;
                    [$rowIndicatorId, $rowIndicatorCreated] = $this->resolveIndicator(
                        $indicatorNameRaw,
                        $this->normalizeText($indicatorNameRaw),
                        $projectId,
                        $projectCodeDisplay,
                        $frameworkType,
                        $frequency,
                        $unit,
                        $indicatorThresholdWarning,
                        $indicatorThresholdCritical
                    );
                    if ($rowIndicatorCreated) {
                        $summary['indicators_created']++;
                    }

                    foreach ($metricColumns as $metric) {
                        $planRaw = $metric['plan_col'] !== null ? $sheet->getCell([$metric['plan_col'], $row])->getFormattedValue() : null;
                        $actualRaw = $metric['actual_col'] !== null ? $sheet->getCell([$metric['actual_col'], $row])->getFormattedValue() : null;

                        $planValue = $this->numericValue($planRaw);
                        $actualValue = $this->numericValue($actualRaw);

                        if (($planValue === null) && ($actualValue === null)) {
                            continue;
                        }

                        $rowHadNumericInput = true;

                        $planInRange = $planValue !== null
                            ? $this->validateDecimal142Value(
                                $summary,
                                $row,
                                $metric['label'],
                                'planned',
                                $planValue,
                                'me_indicator_targets.target_value',
                                $projectNameRaw,
                                $projectCodeRaw,
                                $periodDisplay
                            )
                            : false;
                        $actualInRange = $actualValue !== null
                            ? $this->validateDecimal142Value(
                                $summary,
                                $row,
                                $metric['label'],
                                'actual',
                                $actualValue,
                                'me_indicator_reports.actual_value',
                                $projectNameRaw,
                                $projectCodeRaw,
                                $periodDisplay
                            )
                            : false;

                        if (! $planInRange && ! $actualInRange) {
                            continue;
                        }

                        $metricChanged = false;

                        $indicatorId = $rowIndicatorId;

                        if ($planInRange) {
                            $targetScopeProject = isset($this->targetColumns['scope_project'])
                                ? ($projectCodeDisplay !== '' ? $projectCodeDisplay : null)
                                : null;
                            $targetScopeLocation = isset($this->targetColumns['scope_location']) ? $metric['label'] : null;
                            $targetRecordKey = $this->buildMetricRecordKey($indicatorId, $period['start'], $period['end'], $targetScopeProject, $targetScopeLocation);

                            if (isset($seenTargetKeys[$targetRecordKey])) {
                                $summary['duplicate_target_rows_in_file']++;
                                $firstRow = $seenTargetKeys[$targetRecordKey];
                                $reason = "Duplicate planned metric '{$metric['label']}' for same period/project (first seen on row {$firstRow}). Duplicate value was ignored.";
                                $this->addWarning($summary, "Row {$row}: {$reason}");
                                $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);
                                $rowHadRejectedMetrics = true;

                                $planInRange = false;
                            }

                            if ($planInRange) {
                                $seenTargetKeys[$targetRecordKey] = $row;

                                $targetWrite = $this->upsertTarget(
                                    $indicatorId,
                                    $periodId,
                                    $period['start'],
                                    $period['end'],
                                    $projectCodeDisplay,
                                    $planValue,
                                    $commentRaw,
                                    $targetScopeLocation
                                );
                                if ($targetWrite === 'created') {
                                    $summary['targets_upserted']++;
                                    $summary['targets_created']++;
                                    $rowHadData = true;
                                    $metricChanged = true;
                                } elseif ($targetWrite === 'updated') {
                                    $summary['targets_upserted']++;
                                    $summary['targets_updated']++;
                                    $rowHadData = true;
                                    $metricChanged = true;
                                } else {
                                    $summary['targets_unchanged']++;
                                    $rowHadUnchangedMetrics = true;
                                }
                            }
                        }

                        if ($actualInRange) {
                            $reportScopeProject = isset($this->reportColumns['scope_project'])
                                ? ($projectCodeDisplay !== '' ? $projectCodeDisplay : null)
                                : null;
                            $reportScopeLocation = isset($this->reportColumns['scope_location']) ? $metric['label'] : null;
                            $reportRecordKey = $this->buildMetricRecordKey($indicatorId, $period['start'], $period['end'], $reportScopeProject, $reportScopeLocation);

                            if (isset($seenReportKeys[$reportRecordKey])) {
                                $summary['duplicate_report_rows_in_file']++;
                                $firstRow = $seenReportKeys[$reportRecordKey];
                                $reason = "Duplicate actual metric '{$metric['label']}' for same period/project (first seen on row {$firstRow}). Duplicate value was ignored.";
                                $this->addWarning($summary, "Row {$row}: {$reason}");
                                $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);
                                $rowHadRejectedMetrics = true;

                                $actualInRange = false;
                            }

                            if ($actualInRange) {
                                $seenReportKeys[$reportRecordKey] = $row;

                                $reportWrite = $this->upsertReport(
                                    $indicatorId,
                                    $periodId,
                                    $period['start'],
                                    $period['end'],
                                    $projectCodeDisplay,
                                    $actualValue,
                                    $commentRaw,
                                    $reportScopeLocation
                                );
                                if ($reportWrite === 'created') {
                                    $summary['reports_upserted']++;
                                    $summary['reports_created']++;
                                    $rowHadData = true;
                                    $metricChanged = true;
                                } elseif ($reportWrite === 'updated') {
                                    $summary['reports_upserted']++;
                                    $summary['reports_updated']++;
                                    $rowHadData = true;
                                    $metricChanged = true;
                                } else {
                                    $summary['reports_unchanged']++;
                                    $rowHadUnchangedMetrics = true;
                                }
                            }
                        }

                        if ($metricChanged) {
                            $metricWrites++;
                        }
                    }

                    if ($rowHadData || $rowHadUnchangedMetrics) {
                        $summary['rows_processed']++;
                        $this->addAcceptedRowPreview($summary, $row, $projectNameRaw, $projectCodeDisplay, $periodDisplay, $metricWrites);
                    } else {
                        $summary['rows_skipped']++;
                        if ($rowHadRejectedMetrics) {
                            $this->addWarning($summary, "Row {$row}: numeric values were found but all metric writes were rejected.");
                            $this->addRejectedRow($summary, $row, 'Numeric values were found but all metric writes were rejected (duplicate or validation).', $projectNameRaw, $projectCodeRaw, $periodDisplay);
                        } elseif ($rowHadNumericInput) {
                            $this->addWarning($summary, "Row {$row}: numeric values were found but all were rejected (out of allowed range).");
                            $this->addRejectedRow($summary, $row, 'Numeric values were found but all were rejected as out of allowed range.', $projectNameRaw, $projectCodeRaw, $periodDisplay);
                        } else {
                            $this->addWarning($summary, "Row {$row}: no numeric plan/actual values found.");
                            $this->addRejectedRow($summary, $row, 'No numeric plan/actual values found.', $projectNameRaw, $projectCodeRaw, $periodDisplay);
                        }
                    }
                } catch (Throwable $rowException) {
                    $summary['rows_skipped']++;
                    $summary['rows_failed']++;

                    $reason = $this->formatImportExceptionMessage($rowException);
                    $this->addWarning($summary, "Row {$row}: {$reason}");
                    $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodDisplay);
                }
            }
        });

        $summary['indicator_samples'] = array_slice($this->indicatorCreated, 0, 20);
        $summary['rejected_rows_csv'] = $this->storeRejectedRowsCsv($summary['rejected_rows'] ?? []);
        $summary['debug_report'] = $this->storeDebugReport($summary);
        if (is_string($fileHash) && ($fileHash !== '')) {
            $this->recordImportHistory($fileHash, basename($filePath), $summary);
        }

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

    /**
     * @return array<string, int>|null
     */
    private function detectStrictLongFormatColumns(array $headers): ?array
    {
        $aliases = [
            'project_code' => ['project_code', 'project code', 'project number', 'project no', 'project #'],
            'project_name' => ['project_name', 'project name', 'project title'],
            'project_start_date' => ['project_start_date', 'project start date', 'project start'],
            'project_end_date' => ['project_end_date', 'project end date', 'project end'],
            'indicator_name' => ['indicator_name', 'indicator name', 'metric name'],
            'framework_type' => ['framework_type', 'framework type'],
            'frequency' => ['frequency'],
            'unit' => ['unit'],
            'indicator_threshold_warning' => ['indicator_threshold_warning', 'threshold_warning', 'warning_threshold'],
            'indicator_threshold_critical' => ['indicator_threshold_critical', 'threshold_critical', 'critical_threshold'],
            'target_name' => ['target_name', 'target name', 'segment', 'group', 'scope_location'],
            'period_start' => ['period_start', 'period start', 'reporting period start'],
            'period_end' => ['period_end', 'period end', 'reporting period end'],
            'planned_value' => ['planned_value', 'planned value', 'plan value', 'target value'],
            'actual_value' => ['actual_value', 'actual value', 'reported value', 'result value'],
            'report_time' => ['report_time', 'report time', 'report timestamp', 'reported_at', 'entered_at', 'submitted_at'],
            'report_comment' => ['report_description', 'report_comment', 'report comment', 'comment', 'notes', 'description'],
        ];

        $resolved = [];
        foreach ($headers as $col => $header) {
            $normalized = mb_strtolower($this->cleanHeader($header));
            $normalized = str_replace(['-', '.', '  '], [' ', ' ', ' '], $normalized);
            $normalized = trim((string) preg_replace('/\s+/u', ' ', $normalized));
            $flat = str_replace(' ', '_', $normalized);

            foreach ($aliases as $key => $candidates) {
                if (isset($resolved[$key])) {
                    continue;
                }

                foreach ($candidates as $candidate) {
                    $candidateNormalized = mb_strtolower(trim($candidate));
                    $candidateFlat = str_replace(' ', '_', $candidateNormalized);

                    if (($normalized === $candidateNormalized) || ($flat === $candidateFlat)) {
                        $resolved[$key] = $col;
                        break;
                    }
                }
            }
        }

        $requiredKeys = [
            'project_code',
            'project_name',
            'project_start_date',
            'project_end_date',
            'indicator_name',
            'framework_type',
            'frequency',
            'unit',
            'indicator_threshold_warning',
            'indicator_threshold_critical',
            'target_name',
            'period_start',
            'period_end',
            'planned_value',
            'actual_value',
        ];

        foreach ($requiredKeys as $requiredKey) {
            if (! isset($resolved[$requiredKey])) {
                return null;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, int>  $cols
     * @return array<string, mixed>
     */
    private function importStrictLongFormat(
        mixed $sheet,
        int $highestDataRow,
        array $cols,
        bool $hasProjects,
        bool $hasPeriods,
        ?string $fileHash,
        string $filePath,
        ?string $alreadyImportedAt = null
    ): array {
        if (! $hasProjects) {
            throw new RuntimeException('Strict periodic import requires me_projects table.');
        }

        $summary = [
            'sheet' => (string) $sheet->getTitle(),
            'rows_total' => max(0, $highestDataRow - 1),
            'rows_processed' => 0,
            'rows_skipped' => 0,
            'rows_failed' => 0,
            'projects_created' => 0,
            'periods_created' => 0,
            'metrics_detected' => 0,
            'indicators_created' => 0,
            'targets_upserted' => 0,
            'targets_created' => 0,
            'targets_updated' => 0,
            'targets_unchanged' => 0,
            'reports_upserted' => 0,
            'reports_created' => 0,
            'reports_updated' => 0,
            'reports_unchanged' => 0,
            'duplicate_rows_in_file' => 0,
            'duplicate_target_rows_in_file' => 0,
            'duplicate_report_rows_in_file' => 0,
            'warnings' => [],
            'rejected_rows' => [],
            'accepted_rows_preview' => [],
            'rejected_rows_csv' => null,
            'indicator_samples' => [],
            'generated_at' => now()->toDateTimeString(),
            'file_hash' => $fileHash,
            'already_imported_at' => $alreadyImportedAt,
        ];

        DB::connection()->disableQueryLog();

        DB::transaction(function () use (
            $sheet,
            $highestDataRow,
            $cols,
            $hasPeriods,
            &$summary
        ): void {
            $seenRows = [];
            $seenIndicators = [];

            for ($row = 2; $row <= $highestDataRow; $row++) {
                $projectCodeRaw = $this->stringValue($sheet->getCell([$cols['project_code'], $row])->getFormattedValue());
                $projectNameRaw = $this->stringValue($sheet->getCell([$cols['project_name'], $row])->getFormattedValue());
                $projectStartRaw = $this->stringValue($sheet->getCell([$cols['project_start_date'], $row])->getFormattedValue());
                $projectEndRaw = $this->stringValue($sheet->getCell([$cols['project_end_date'], $row])->getFormattedValue());
                $indicatorNameRaw = $this->stringValue($sheet->getCell([$cols['indicator_name'], $row])->getFormattedValue());
                $frameworkTypeRaw = $this->stringValue($sheet->getCell([$cols['framework_type'], $row])->getFormattedValue());
                $frequencyRaw = $this->stringValue($sheet->getCell([$cols['frequency'], $row])->getFormattedValue());
                $unitRaw = $this->stringValue($sheet->getCell([$cols['unit'], $row])->getFormattedValue());
                $indicatorThresholdWarningRaw = $this->stringValue($sheet->getCell([$cols['indicator_threshold_warning'], $row])->getFormattedValue());
                $indicatorThresholdCriticalRaw = $this->stringValue($sheet->getCell([$cols['indicator_threshold_critical'], $row])->getFormattedValue());
                $targetNameRaw = $this->stringValue($sheet->getCell([$cols['target_name'], $row])->getFormattedValue());
                $periodStartRaw = $this->stringValue($sheet->getCell([$cols['period_start'], $row])->getFormattedValue());
                $periodEndRaw = $this->stringValue($sheet->getCell([$cols['period_end'], $row])->getFormattedValue());
                $plannedRaw = $this->stringValue($sheet->getCell([$cols['planned_value'], $row])->getFormattedValue());
                $actualRaw = $this->stringValue($sheet->getCell([$cols['actual_value'], $row])->getFormattedValue());
                $reportTimeRaw = isset($cols['report_time'])
                    ? $this->stringValue($sheet->getCell([$cols['report_time'], $row])->getFormattedValue())
                    : '';
                $reportCommentRaw = isset($cols['report_comment'])
                    ? $this->stringValue($sheet->getCell([$cols['report_comment'], $row])->getFormattedValue())
                    : '';

                if ($this->isRowEmpty([
                    $projectCodeRaw,
                    $projectNameRaw,
                    $indicatorNameRaw,
                    $targetNameRaw,
                    $periodStartRaw,
                    $periodEndRaw,
                    $plannedRaw,
                    $actualRaw,
                ])) {
                    $summary['rows_skipped']++;

                    continue;
                }

                try {
                    $missing = [];
                    $requiredValues = [
                        'project_code' => $projectCodeRaw,
                        'project_name' => $projectNameRaw,
                        'project_start_date' => $projectStartRaw,
                        'project_end_date' => $projectEndRaw,
                        'indicator_name' => $indicatorNameRaw,
                        'framework_type' => $frameworkTypeRaw,
                        'frequency' => $frequencyRaw,
                        'unit' => $unitRaw,
                        'indicator_threshold_warning' => $indicatorThresholdWarningRaw,
                        'indicator_threshold_critical' => $indicatorThresholdCriticalRaw,
                        'target_name' => $targetNameRaw,
                        'period_start' => $periodStartRaw,
                        'period_end' => $periodEndRaw,
                        'planned_value' => $plannedRaw,
                    ];

                    foreach ($requiredValues as $key => $value) {
                        if ($this->stringValue($value) === '') {
                            $missing[] = $key;
                        }
                    }

                    if ($missing !== []) {
                        $summary['rows_skipped']++;
                        $reason = 'Missing required value(s): ' . implode(', ', $missing) . '.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, "{$periodStartRaw} to {$periodEndRaw}");

                        continue;
                    }

                    $projectStart = $this->parseDateToken($projectStartRaw);
                    $projectEnd = $this->parseDateToken($projectEndRaw);
                    $periodStart = $this->parseDateToken($periodStartRaw);
                    $periodEnd = $this->parseDateToken($periodEndRaw);

                    if (($projectStart === null) || ($projectEnd === null)) {
                        $summary['rows_skipped']++;
                        $reason = 'Invalid project start/end date format.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, "{$periodStartRaw} to {$periodEndRaw}");

                        continue;
                    }

                    if (($periodStart === null) || ($periodEnd === null)) {
                        $summary['rows_skipped']++;
                        $reason = 'Invalid period start/end date format.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, "{$periodStartRaw} to {$periodEndRaw}");

                        continue;
                    }

                    if ($projectEnd->lt($projectStart)) {
                        $summary['rows_skipped']++;
                        $reason = 'Project end date must be on/after project start date.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, "{$periodStartRaw} to {$periodEndRaw}");

                        continue;
                    }

                    if ($periodEnd->lt($periodStart)) {
                        $summary['rows_skipped']++;
                        $reason = 'Period end date must be on/after period start date.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, "{$periodStartRaw} to {$periodEndRaw}");

                        continue;
                    }

                    $plannedValue = $this->numericValue($plannedRaw);
                    $actualValue = $this->numericValue($actualRaw);
                    $actualProvided = $this->stringValue($actualRaw) !== '';

                    if ($plannedValue === null) {
                        $summary['rows_skipped']++;
                        $reason = 'planned_value must be numeric.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    if ($actualProvided && ($actualValue === null)) {
                        $summary['rows_skipped']++;
                        $reason = 'actual_value must be numeric when provided.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    $frameworkType = mb_strtolower(trim($frameworkTypeRaw));
                    $frequency = mb_strtolower(trim($frequencyRaw));
                    $allowedFrameworks = ['output', 'outcome', 'impact'];
                    $allowedFrequencies = ['weekly', 'monthly', 'quarterly', 'semiannual', 'annual'];
                    $indicatorThresholdWarning = $this->numericValue($indicatorThresholdWarningRaw);
                    $indicatorThresholdCritical = $this->numericValue($indicatorThresholdCriticalRaw);

                    if (! in_array($frameworkType, $allowedFrameworks, true)) {
                        $summary['rows_skipped']++;
                        $reason = "Invalid framework_type '{$frameworkTypeRaw}'. Allowed: output, outcome, impact.";
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    if (! in_array($frequency, $allowedFrequencies, true)) {
                        $summary['rows_skipped']++;
                        $reason = "Invalid frequency '{$frequencyRaw}'. Allowed: weekly, monthly, quarterly, semiannual, annual.";
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    if ($frequency === 'weekly') {
                        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
                        if ($daysInPeriod !== 7) {
                            $summary['rows_skipped']++;
                            $reason = 'For weekly frequency, period_start and period_end must cover exactly 7 days.';
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                            continue;
                        }
                    }

                    if (($indicatorThresholdWarning === null) || ($indicatorThresholdCritical === null)) {
                        $summary['rows_skipped']++;
                        $reason = 'indicator_threshold_warning and indicator_threshold_critical must be numeric.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    if (($indicatorThresholdWarning < 0) || ($indicatorThresholdWarning > 100) || ($indicatorThresholdCritical < 0) || ($indicatorThresholdCritical > 100)) {
                        $summary['rows_skipped']++;
                        $reason = 'Indicator thresholds must be within 0 to 100.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    if ($indicatorThresholdCritical > $indicatorThresholdWarning) {
                        $summary['rows_skipped']++;
                        $reason = 'indicator_threshold_critical cannot be greater than indicator_threshold_warning.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    [$projectId, $projectCodeDisplay, $projectCreated] = $this->resolveProjectForStrictImport(
                        $projectCodeRaw,
                        $projectNameRaw,
                        $projectStart->toDateString(),
                        $projectEnd->toDateString()
                    );

                    if ($projectCreated) {
                        $summary['projects_created']++;
                    }

                    $periodId = null;
                    if ($hasPeriods) {
                        $periodType = in_array($frequency, ['weekly', 'monthly', 'quarterly', 'semiannual', 'annual'], true)
                            ? $frequency
                            : 'weekly';
                        [$periodId, $periodCreated] = $this->resolvePeriod(
                            $projectId,
                            $periodStart->toDateString(),
                            $periodEnd->toDateString(),
                            "{$frequency} {$periodStart->toDateString()} - {$periodEnd->toDateString()}",
                            $periodType
                        );

                        if ($periodCreated) {
                            $summary['periods_created']++;
                        }
                    }

                    [$indicatorId, $indicatorCode, $indicatorCreated] = $this->resolveIndicatorForStrictImport(
                        $projectId,
                        $projectCodeDisplay,
                        $indicatorNameRaw,
                        $frameworkType,
                        $frequency,
                        $unitRaw,
                        $indicatorThresholdWarning,
                        $indicatorThresholdCritical
                    );

                    if ($indicatorCreated) {
                        $summary['indicators_created']++;
                    }

                    $seenIndicators[$indicatorId] = true;

                    $targetName = trim($targetNameRaw);
                    $targetCode = $this->generateTargetCode($indicatorCode, $targetName);
                    $rowKey = mb_strtolower($projectCodeDisplay . '|' . $indicatorCode . '|' . $targetName . '|' . $periodStart->toDateString() . '|' . $periodEnd->toDateString());

                    if (isset($seenRows[$rowKey])) {
                        $summary['rows_skipped']++;
                        $summary['duplicate_rows_in_file']++;
                        $firstRow = $seenRows[$rowKey];
                        $reason = "Duplicate key for project/indicator/target/period (first seen on row {$firstRow}).";
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    $seenRows[$rowKey] = $row;

                    $plannedInRange = $this->validateDecimal142Value(
                        $summary,
                        $row,
                        "{$indicatorNameRaw} ({$targetName})",
                        'planned',
                        $plannedValue,
                        'me_indicator_targets.target_value',
                        $projectNameRaw,
                        $projectCodeRaw,
                        $periodStart->toDateString() . ' to ' . $periodEnd->toDateString()
                    );
                    $actualInRange = ! $actualProvided || ($actualValue === null)
                        ? false
                        : $this->validateDecimal142Value(
                            $summary,
                            $row,
                            "{$indicatorNameRaw} ({$targetName})",
                            'actual',
                            $actualValue,
                            'me_indicator_reports.actual_value',
                            $projectNameRaw,
                            $projectCodeRaw,
                            $periodStart->toDateString() . ' to ' . $periodEnd->toDateString()
                        );

                    if (! $plannedInRange || ($actualProvided && ! $actualInRange)) {
                        $summary['rows_skipped']++;
                        $this->addRejectedRow($summary, $row, 'Planned/actual value out of allowed range.', $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    $reportTime = null;
                    if ($this->stringValue($reportTimeRaw) !== '') {
                        $reportTime = $this->parseDateToken($reportTimeRaw);

                        if ($reportTime === null) {
                            $summary['rows_skipped']++;
                            $reason = "Invalid report_time '{$reportTimeRaw}'. Use a valid date/time format.";
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                            continue;
                        }
                    }

                    if ($actualInRange && ($actualValue !== null) && $this->reportExistsForSamePeriod(
                        $indicatorId,
                        $periodStart->toDateString(),
                        $periodEnd->toDateString(),
                        $projectCodeDisplay,
                        $targetName
                    )) {
                        $summary['rows_skipped']++;
                        $summary['duplicate_report_rows_in_file']++;
                        $reason = 'Report already exists for this project + indicator + target + period. Duplicate period report is not allowed.';
                        $this->addWarning($summary, "Row {$row}: {$reason}");
                        $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());

                        continue;
                    }

                    $targetWrite = $this->upsertTarget(
                        $indicatorId,
                        $periodId,
                        $periodStart->toDateString(),
                        $periodEnd->toDateString(),
                        $projectCodeDisplay,
                        $plannedValue,
                        $targetCode,
                        $targetName
                    );
                    if ($targetWrite === 'created') {
                        $summary['targets_upserted']++;
                        $summary['targets_created']++;
                    } elseif ($targetWrite === 'updated') {
                        $summary['targets_upserted']++;
                        $summary['targets_updated']++;
                    } else {
                        $summary['targets_unchanged']++;
                    }

                    if ($actualInRange && ($actualValue !== null)) {
                        $reportWrite = $this->upsertReport(
                            $indicatorId,
                            $periodId,
                            $periodStart->toDateString(),
                            $periodEnd->toDateString(),
                            $projectCodeDisplay,
                            $actualValue,
                            $reportCommentRaw,
                            $targetName,
                            $reportTime,
                            $frequency
                        );
                        if ($reportWrite === 'created') {
                            $summary['reports_upserted']++;
                            $summary['reports_created']++;
                            $this->syncAlertsForImportedReport(
                                $indicatorId,
                                $periodStart->toDateString(),
                                $periodEnd->toDateString(),
                                $projectCodeDisplay,
                                $targetName
                            );
                        } elseif ($reportWrite === 'updated') {
                            $summary['reports_upserted']++;
                            $summary['reports_updated']++;
                            $this->syncAlertsForImportedReport(
                                $indicatorId,
                                $periodStart->toDateString(),
                                $periodEnd->toDateString(),
                                $projectCodeDisplay,
                                $targetName
                            );
                        } elseif ($reportWrite === 'period_locked') {
                            $summary['rows_skipped']++;
                            $summary['duplicate_report_rows_in_file']++;
                            $reason = 'Report already exists for this target/period. Row was rejected.';
                            $this->addWarning($summary, "Row {$row}: {$reason}");
                            $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());
                            continue;
                        } else {
                            $summary['reports_unchanged']++;
                        }
                    }

                    $summary['rows_processed']++;
                    $this->addAcceptedRowPreview(
                        $summary,
                        $row,
                        $projectNameRaw,
                        $projectCodeDisplay,
                        $periodStart->toDateString() . ' to ' . $periodEnd->toDateString(),
                        1
                    );
                } catch (Throwable $rowException) {
                    $summary['rows_skipped']++;
                    $summary['rows_failed']++;

                    $reason = $this->formatImportExceptionMessage($rowException);
                    $this->addWarning($summary, "Row {$row}: {$reason}");
                    $this->addRejectedRow($summary, $row, $reason, $projectNameRaw, $projectCodeRaw, "{$periodStartRaw} to {$periodEndRaw}");
                }
            }

            $summary['metrics_detected'] = count($seenIndicators);
        });

        $summary['indicator_samples'] = array_slice($this->indicatorCreated, 0, 20);
        $summary['rejected_rows_csv'] = $this->storeRejectedRowsCsv($summary['rejected_rows'] ?? []);
        $summary['debug_report'] = $this->storeDebugReport($summary);
        if (is_string($fileHash) && ($fileHash !== '')) {
            $this->recordImportHistory($fileHash, basename($filePath), $summary);
        }

        return $summary;
    }
    private function detectCoreColumns(array $headers): array
    {
        $timestampAliases = ['timestamp', 'submission time', 'submitted at'];
        $projectNameAliases = ['project_name', 'project name', 'project title'];
        $projectCodeAliases = ['project_code', 'project code', 'project number', 'project no', 'project #'];
        $periodAliases = ['reporting period covered', 'reporting period', 'period covered', 'period'];
        $commentAliases = ['additional comment', 'comment', 'remarks', 'notes', 'report_comment'];
        $indicatorNameAliases = ['indicator_name', 'indicator name'];

        $projectStartAliases = ['project_start_date', 'project start date', 'project start'];
        $projectEndAliases = ['project_end_date', 'project end date', 'project end'];
        $periodStartAliases = ['period_start', 'period start', 'reporting period start'];
        $periodEndAliases = ['period_end', 'period end', 'reporting period end'];
        $frameworkTypeAliases = ['framework_type', 'framework type'];
        $frequencyAliases = ['frequency'];
        $unitAliases = ['unit'];
        $indicatorThresholdWarningAliases = ['indicator_threshold_warning', 'threshold_warning', 'warning_threshold'];
        $indicatorThresholdCriticalAliases = ['indicator_threshold_critical', 'threshold_critical', 'critical_threshold'];

        $timestampCol = null;
        $projectNameCol = null;
        $projectCodeCol = null;
        $periodCol = null;
        $commentCol = null;
        $projectStartCol = null;
        $projectEndCol = null;
        $periodStartCol = null;
        $periodEndCol = null;
        $frameworkTypeCol = null;
        $frequencyCol = null;
        $unitCol = null;
        $indicatorNameCol = null;
        $indicatorThresholdWarningCol = null;
        $indicatorThresholdCriticalCol = null;

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
            if (($projectStartCol === null) && $this->containsAnyNormalized($normalized, $projectStartAliases)) {
                $projectStartCol = $col;
            }
            if (($projectEndCol === null) && $this->containsAnyNormalized($normalized, $projectEndAliases)) {
                $projectEndCol = $col;
            }
            if (($periodStartCol === null) && $this->containsAnyNormalized($normalized, $periodStartAliases)) {
                $periodStartCol = $col;
            }
            if (($periodEndCol === null) && $this->containsAnyNormalized($normalized, $periodEndAliases)) {
                $periodEndCol = $col;
            }
            if (($frameworkTypeCol === null) && $this->containsAnyNormalized($normalized, $frameworkTypeAliases)) {
                $frameworkTypeCol = $col;
            }
            if (($frequencyCol === null) && $this->containsAnyNormalized($normalized, $frequencyAliases)) {
                $frequencyCol = $col;
            }
            if (($unitCol === null) && $this->containsAnyNormalized($normalized, $unitAliases)) {
                $unitCol = $col;
            }
            if (($indicatorNameCol === null) && $this->containsAnyNormalized($normalized, $indicatorNameAliases)) {
                $indicatorNameCol = $col;
            }
            if (($indicatorThresholdWarningCol === null) && $this->containsAnyNormalized($normalized, $indicatorThresholdWarningAliases)) {
                $indicatorThresholdWarningCol = $col;
            }
            if (($indicatorThresholdCriticalCol === null) && $this->containsAnyNormalized($normalized, $indicatorThresholdCriticalAliases)) {
                $indicatorThresholdCriticalCol = $col;
            }
        }

        $hasPeriodRangeColumns = ($periodStartCol !== null) && ($periodEndCol !== null);
        if (($projectNameCol === null) || ($projectCodeCol === null) || (($periodCol === null) && (! $hasPeriodRangeColumns))) {
            throw new RuntimeException('Missing required columns. Ensure project name, project code/project number, and period column(s) exist.');
        }

        $schemaMode = ($projectStartCol !== null)
            && ($projectEndCol !== null)
            && ($periodStartCol !== null)
            && ($periodEndCol !== null)
            && ($frameworkTypeCol !== null)
            && ($frequencyCol !== null)
            && ($unitCol !== null)
            && ($indicatorNameCol !== null)
            && ($indicatorThresholdWarningCol !== null)
            && ($indicatorThresholdCriticalCol !== null);

        return [
            'timestamp' => $timestampCol ?? 1,
            'project_name' => $projectNameCol,
            'project_code' => $projectCodeCol,
            'period' => $periodCol,
            'comment' => $commentCol,
            'project_start_date' => $projectStartCol,
            'project_end_date' => $projectEndCol,
            'period_start' => $periodStartCol,
            'period_end' => $periodEndCol,
            'framework_type' => $frameworkTypeCol,
            'frequency' => $frequencyCol,
            'unit' => $unitCol,
            'indicator_name' => $indicatorNameCol,
            'indicator_threshold_warning' => $indicatorThresholdWarningCol,
            'indicator_threshold_critical' => $indicatorThresholdCriticalCol,
            'schema_mode' => $schemaMode,
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
        $actualKeywords = array_merge(self::ACTUAL_KEYWORDS, ['reported', 'result', 'performance', 'achievement']);
        $planKeywords = array_merge(self::PLAN_KEYWORDS, ['projection', 'expected', 'planed']);

        foreach ($actualKeywords as $keyword) {
            if (str_contains($headerLower, mb_strtolower($keyword))) {
                return 'actual';
            }
        }

        foreach ($planKeywords as $keyword) {
            if (str_contains($headerLower, mb_strtolower($keyword))) {
                return 'plan';
            }
        }

        return null;
    }

    private function metricLabelFromHeader(string $header): string
    {
        $label = preg_replace('/^\s*\d+\s*[\.\)\-:]*\s*/u', '', $header);
        $label = (string) $label;
        $label = str_replace(['_', '|'], ' ', $label);

        $label = $this->stripMetricKeywords($label);
        $label = preg_replace('/\s*(\(|\[)\s*(plan|planned|target|actual|achieved|reported|result)\s*(\)|\])\s*/iu', ' ', (string) $label);

        $label = preg_replace('/\s+/u', ' ', (string) $label);
        $label = trim((string) $label, " \t\n\r\0\x0B-:,.;|/_()[]{}<>\"'");
        $label = preg_replace('/\s{2,}/u', ' ', (string) $label);
        $label = trim((string) $label);

        return $label !== '' ? $label : $header;
    }

    private function stripMetricKeywords(string $label): string
    {
        $keywords = array_merge(
            self::PLAN_KEYWORDS,
            self::ACTUAL_KEYWORDS,
            ['reported', 'result', 'performance', 'achievement', 'projection', 'expected', 'planed', 'Ã¡Ë†ËœÃ¡Å’Â Ã¡Å â€¢']
        );
        $keywords = array_values(array_unique(array_map(static fn (string $k): string => trim($k), $keywords)));
        usort($keywords, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }

            if (preg_match('/^[a-z0-9 _\-\/]+$/i', $keyword) === 1) {
                $pattern = '/\\b' . preg_quote($keyword, '/') . '\\b/iu';
                $label = preg_replace($pattern, ' ', $label) ?? $label;
            } else {
                $label = str_ireplace($keyword, ' ', $label);
            }
        }

        return $label;
    }
    private function resolveProject(
        string $projectNameRaw,
        string $projectCodeRaw,
        bool $hasProjects,
        ?string $projectStartDate = null,
        ?string $projectEndDate = null,
        bool $enforceProjectProfile = false
    ): array
    {
        $projectName = $projectNameRaw !== '' ? $projectNameRaw : 'Unnamed Project';
        $resolvedCode = $this->resolveProjectCode($projectCodeRaw, $projectName);
        $projectCodeCanonical = $this->normalizeProjectCode($resolvedCode);
        $projectCodeDisplay = $this->formatProjectCode($projectCodeCanonical !== '' ? $projectCodeCanonical : $resolvedCode);

        if (! $hasProjects) {
            return [null, $projectCodeDisplay, false];
        }

        if ($enforceProjectProfile) {
            if (($projectStartDate === null) || ($projectEndDate === null)) {
                throw new RuntimeException('project_start_date and project_end_date are required in schema mode.');
            }

            return $this->resolveProjectForStrictImport($projectCodeRaw, $projectNameRaw, $projectStartDate, $projectEndDate);
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

    /**
     * @return array{0:int,1:string,2:bool}
     */
    private function resolveProjectForStrictImport(
        string $projectCodeRaw,
        string $projectNameRaw,
        string $projectStartDate,
        string $projectEndDate
    ): array {
        $projectName = $this->stringValue($projectNameRaw);
        if ($projectName === '') {
            throw new RuntimeException('Project name is required.');
        }

        $resolvedCode = $this->resolveProjectCode($projectCodeRaw, $projectName);
        $projectCodeCanonical = $this->normalizeProjectCode($resolvedCode);
        $projectCodeDisplay = $this->formatProjectCode($projectCodeCanonical !== '' ? $projectCodeCanonical : $resolvedCode);
        if ($projectCodeDisplay === '') {
            throw new RuntimeException('Project code is required.');
        }

        $existing = DB::table('me_projects')
            ->where('project_code', $projectCodeDisplay)
            ->first();

        if ($existing !== null) {
            $existingNameKey = $this->normalizeProjectNameKey((string) ($existing->name ?? ''));
            $incomingNameKey = $this->normalizeProjectNameKey($projectName);
            if (($existingNameKey !== '') && ($incomingNameKey !== '') && ($existingNameKey !== $incomingNameKey)) {
                throw new RuntimeException("Project code '{$projectCodeDisplay}' already exists with a different project name.");
            }

            $existingStart = $existing->start_date ? (string) $existing->start_date : null;
            $existingEnd = $existing->end_date ? (string) $existing->end_date : null;
            if (($existingStart !== null) && ($existingStart !== $projectStartDate)) {
                throw new RuntimeException("Project code '{$projectCodeDisplay}' start date mismatch with existing project.");
            }
            if (($existingEnd !== null) && ($existingEnd !== $projectEndDate)) {
                throw new RuntimeException("Project code '{$projectCodeDisplay}' end date mismatch with existing project.");
            }

            return [(int) $existing->id, $projectCodeDisplay, false];
        }

        $insert = [
            'name' => $projectName,
            'project_code' => $projectCodeDisplay,
            'description' => null,
            'start_date' => $projectStartDate,
            'end_date' => $projectEndDate,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $projectId = (int) DB::table('me_projects')->insertGetId($insert);

        if ($projectCodeCanonical !== '') {
            $this->projectCodeCache[$projectCodeCanonical] = $projectId;
        }
        $nameKey = $this->normalizeProjectNameKey($projectName);
        if ($nameKey !== '') {
            $this->projectNameCache[$nameKey] = $projectId;
        }

        return [$projectId, $projectCodeDisplay, true];
    }

    private function resolvePeriod(int $projectId, string $startDate, string $endDate, string $label, string $type = 'weekly'): array
    {
        $normalizedType = mb_strtolower(trim($type));
        if (! in_array($normalizedType, ['weekly', 'baseline', 'midline', 'endline'], true)) {
            $normalizedType = 'weekly';
        }

        $key = "{$projectId}|{$normalizedType}|{$startDate}|{$endDate}";
        if (isset($this->periodCache[$key])) {
            return [$this->periodCache[$key], false];
        }

        $existingId = DB::table('me_reporting_periods')
            ->where('project_id', $projectId)
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->where('type', $normalizedType)
            ->value('id');

        if ($existingId !== null) {
            $this->periodCache[$key] = (int) $existingId;

            return [(int) $existingId, false];
        }

        $insert = [
            'project_id' => $projectId,
            'type' => $normalizedType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label !== '' ? $label : ucfirst($normalizedType) . " {$startDate} - {$endDate}",
            'is_locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = (int) DB::table('me_reporting_periods')->insertGetId($insert);
        $this->periodCache[$key] = $id;

        return [$id, true];
    }

    /**
     * @return array{0:int,1:string,2:bool}
     */
    private function resolveIndicatorForStrictImport(
        int $projectId,
        string $projectCode,
        string $indicatorNameRaw,
        string $frameworkType,
        string $frequency,
        string $unitRaw,
        ?float $thresholdWarning = null,
        ?float $thresholdCritical = null
    ): array {
        $indicatorName = $this->stringValue($indicatorNameRaw);
        if ($indicatorName === '') {
            throw new RuntimeException('Indicator name is required.');
        }

        $metricKey = $this->normalizeText($indicatorName);
        $code = $this->generateIndicatorCode($projectCode, $indicatorName, $metricKey !== '' ? $metricKey : md5($indicatorName));
        $unit = $this->stringValue($unitRaw);
        if ($unit === '') {
            $unit = 'count';
        }
        $resolvedThresholdWarning = $thresholdWarning !== null ? round($thresholdWarning, 2) : 70.0;
        $resolvedThresholdCritical = $thresholdCritical !== null ? round($thresholdCritical, 2) : 50.0;

        $existing = DB::table('me_indicators')
            ->where('code', $code)
            ->first();

        if ($existing !== null) {
            $existingNameKey = $this->normalizeText((string) ($existing->name ?? ''));
            if (($existingNameKey !== '') && ($existingNameKey !== $this->normalizeText($indicatorName))) {
                throw new RuntimeException("Indicator code '{$code}' already exists with a different indicator name.");
            }

            return [(int) $existing->id, $code, false];
        }

        [$dataType] = $this->inferIndicatorType($indicatorName);
        $insert = [
            'code' => $code,
            'name' => $indicatorName,
            'framework_type' => $frameworkType,
            'unit' => $unit,
            'frequency' => $frequency,
            'description' => null,
            'is_active' => true,
            'disaggregation_required' => false,
            'threshold_warning' => $resolvedThresholdWarning,
            'threshold_critical' => $resolvedThresholdCritical,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (isset($this->indicatorColumns['project_id'])) {
            $insert['project_id'] = $projectId;
        }
        if (isset($this->indicatorColumns['name_local'])) {
            $insert['name_local'] = $indicatorName;
        }
        if (isset($this->indicatorColumns['direction'])) {
            $insert['direction'] = 'higher_is_better';
        }
        if (isset($this->indicatorColumns['data_type'])) {
            $insert['data_type'] = $dataType;
        }

        $indicatorId = (int) DB::table('me_indicators')->insertGetId($insert);
        $this->indicatorCodeCache[$code] = $indicatorId;
        $labelKey = $this->indicatorLabelKey($projectId, $indicatorName);
        if ($labelKey !== '') {
            $this->indicatorLabelCache[$labelKey] = $indicatorId;
        }
        $this->indicatorCreated[] = [
            'code' => $code,
            'name' => $indicatorName,
            'project_code' => $projectCode,
        ];

        return [$indicatorId, $code, true];
    }

    private function generateTargetCode(string $indicatorCode, string $targetName): string
    {
        $cleanTarget = strtoupper((string) preg_replace('/[^A-Z0-9]+/', '_', $this->stringValue($targetName)));
        $cleanTarget = trim($cleanTarget, '_');
        if ($cleanTarget === '') {
            $cleanTarget = 'TARGET';
        }

        return substr($indicatorCode . '_' . $cleanTarget, 0, 100);
    }

    private function resolveIndicator(
        string $metricLabel,
        string $metricKey,
        ?int $projectId,
        string $projectCode,
        ?string $frameworkType = null,
        ?string $frequency = null,
        ?string $unitOverride = null,
        ?float $thresholdWarning = null,
        ?float $thresholdCritical = null
    ): array
    {
        $projectKey = $projectId ?? 0;
        $labelKey = $this->indicatorLabelKey($projectKey, $metricLabel);

        if (($labelKey !== '') && isset($this->indicatorLabelCache[$labelKey])) {
            return [$this->indicatorLabelCache[$labelKey], false];
        }

        [$dataType, $inferredUnit] = $this->inferIndicatorType($metricLabel);
        $normalizedFramework = mb_strtolower(trim((string) ($frameworkType ?? '')));
        $normalizedFrequency = mb_strtolower(trim((string) ($frequency ?? '')));
        $allowedFrameworks = ['output', 'outcome', 'impact'];
        $allowedFrequencies = ['weekly', 'monthly', 'quarterly', 'semiannual', 'annual'];
        $resolvedFramework = in_array($normalizedFramework, $allowedFrameworks, true) ? $normalizedFramework : 'output';
        $resolvedFrequency = in_array($normalizedFrequency, $allowedFrequencies, true) ? $normalizedFrequency : 'weekly';
        $unit = $this->stringValue($unitOverride);
        if ($unit === '') {
            $unit = $inferredUnit;
        }
        $resolvedThresholdWarning = $thresholdWarning !== null ? round($thresholdWarning, 2) : 70.0;
        $resolvedThresholdCritical = $thresholdCritical !== null ? round($thresholdCritical, 2) : 50.0;
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
            'framework_type' => $resolvedFramework,
            'unit' => $unit,
            'frequency' => $resolvedFrequency,
            'description' => null,
            'is_active' => true,
            'disaggregation_required' => false,
            'threshold_warning' => $resolvedThresholdWarning,
            'threshold_critical' => $resolvedThresholdCritical,
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
        ?string $notes,
        ?string $scopeLocation = null
    ): string {
        $match = [
            'indicator_id' => $indicatorId,
            'period_start' => $startDate,
            'period_end' => $endDate,
        ];

        if (isset($this->targetColumns['scope_project'])) {
            $match['scope_project'] = $projectCode !== '' ? $projectCode : null;
        }
        if (isset($this->targetColumns['scope_location'])) {
            $match['scope_location'] = $scopeLocation !== null && trim($scopeLocation) !== '' ? trim($scopeLocation) : null;
        }

        $targetRounded = round($targetValue, 2);
        $targetSelect = ['id', 'target_value'];
        if (isset($this->targetColumns['reporting_period_id'])) {
            $targetSelect[] = 'reporting_period_id';
        }
        if (isset($this->targetColumns['notes'])) {
            $targetSelect[] = 'notes';
        }
        $existing = DB::table('me_indicator_targets')
            ->select($targetSelect)
            ->where($match)
            ->first();

        $payload = [
            'target_value' => $targetRounded,
        ];

        if (isset($this->targetColumns['reporting_period_id'])) {
            $payload['reporting_period_id'] = $periodId;
        }
        if (isset($this->targetColumns['notes'])) {
            $payload['notes'] = $notes;
        }

        if ($existing !== null) {
            $sameTarget = number_format((float) ($existing->target_value ?? 0), 2, '.', '') === number_format($targetRounded, 2, '.', '');
            $samePeriod = ! isset($this->targetColumns['reporting_period_id'])
                || ((int) ($existing->reporting_period_id ?? 0) === (int) ($periodId ?? 0));
            $sameNotes = ! isset($this->targetColumns['notes'])
                || ($this->normalizeNullableString($existing->notes ?? null) === $this->normalizeNullableString($notes));

            if ($sameTarget && $samePeriod && $sameNotes) {
                return 'unchanged';
            }

            $update = array_merge($payload, ['updated_at' => now()]);
            DB::table('me_indicator_targets')->where('id', $existing->id)->update($update);

            return 'updated';
        }

        $insert = array_merge($match, $payload, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('me_indicator_targets')->insert($insert);

        return 'created';
    }

    private function upsertReport(
        int $indicatorId,
        ?int $periodId,
        string $startDate,
        string $endDate,
        string $projectCode,
        float $actualValue,
        ?string $comment,
        ?string $scopeLocation = null,
        ?Carbon $reportTime = null,
        ?string $frequency = null
    ): string {
        $match = [
            'indicator_id' => $indicatorId,
            'period_start' => $startDate,
            'period_end' => $endDate,
        ];

        if (isset($this->reportColumns['scope_project'])) {
            $match['scope_project'] = $projectCode !== '' ? $projectCode : null;
        }
        if (isset($this->reportColumns['scope_location'])) {
            $match['scope_location'] = $scopeLocation !== null && trim($scopeLocation) !== '' ? trim($scopeLocation) : null;
        }

        $actualRounded = round($actualValue, 2);
        $reportSelect = ['id', 'actual_value'];
        if (isset($this->reportColumns['reporting_period_id'])) {
            $reportSelect[] = 'reporting_period_id';
        }
        if (isset($this->reportColumns['source'])) {
            $reportSelect[] = 'source';
        }
        if (isset($this->reportColumns['comment'])) {
            $reportSelect[] = 'comment';
        }
        if (isset($this->reportColumns['notes'])) {
            $reportSelect[] = 'notes';
        }
        if (isset($this->reportColumns['report_time'])) {
            $reportSelect[] = 'report_time';
        }
        if (isset($this->reportColumns['entered_at'])) {
            $reportSelect[] = 'entered_at';
        }
        $existing = DB::table('me_indicator_reports')
            ->select($reportSelect)
            ->where($match)
            ->first();

        $effectiveReportTime = $reportTime ?? now();

        $payload = [
            'actual_value' => $actualRounded,
        ];

        if (isset($this->reportColumns['reporting_period_id'])) {
            $payload['reporting_period_id'] = $periodId;
        }
        if (isset($this->reportColumns['source'])) {
            $payload['source'] = 'import';
        }
        if (isset($this->reportColumns['comment'])) {
            $payload['comment'] = $comment;
        }
        if (isset($this->reportColumns['notes'])) {
            $payload['notes'] = $comment;
        }
        if (isset($this->reportColumns['report_time'])) {
            $payload['report_time'] = $effectiveReportTime;
        }
        if (isset($this->reportColumns['entered_at'])) {
            $payload['entered_at'] = $effectiveReportTime;
        }

        if ($existing !== null) {
            return 'period_locked';

            $sameActual = number_format((float) ($existing->actual_value ?? 0), 2, '.', '') === number_format($actualRounded, 2, '.', '');
            $samePeriod = ! isset($this->reportColumns['reporting_period_id'])
                || ((int) ($existing->reporting_period_id ?? 0) === (int) ($periodId ?? 0));
            $sameSource = ! isset($this->reportColumns['source'])
                || ((string) ($existing->source ?? 'manual') === 'import');
            $sameComment = ! isset($this->reportColumns['comment'])
                || ($this->normalizeNullableString($existing->comment ?? null) === $this->normalizeNullableString($comment));
            $sameNotes = ! isset($this->reportColumns['notes'])
                || ($this->normalizeNullableString($existing->notes ?? null) === $this->normalizeNullableString($comment));

            if ($sameActual && $samePeriod && $sameSource && $sameComment && $sameNotes) {
                return 'unchanged';
            }

            $update = array_merge($payload, ['updated_at' => now()]);
            if (isset($this->reportColumns['entered_by'])) {
                $update['entered_by'] = Auth::id();
            }
            if (isset($this->reportColumns['entered_at'])) {
                $update['entered_at'] = $effectiveReportTime;
            }

            DB::table('me_indicator_reports')->where('id', $existing->id)->update($update);

            return 'updated';
        }

        $insert = array_merge($match, $payload, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (isset($this->reportColumns['entered_by'])) {
            $insert['entered_by'] = Auth::id();
        }
        if (isset($this->reportColumns['entered_at'])) {
            $insert['entered_at'] = $effectiveReportTime;
        }
        DB::table('me_indicator_reports')->insert($insert);

        return 'created';
    }

    private function reportExistsForSamePeriod(
        int $indicatorId,
        string $startDate,
        string $endDate,
        string $projectCode,
        ?string $scopeLocation = null
    ): bool {
        $query = DB::table('me_indicator_reports')
            ->where('indicator_id', $indicatorId)
            ->where('period_start', $startDate)
            ->where('period_end', $endDate);

        if (isset($this->reportColumns['scope_project'])) {
            $query->where('scope_project', $projectCode !== '' ? $projectCode : null);
        }

        if (isset($this->reportColumns['scope_location'])) {
            $query->where('scope_location', $scopeLocation !== null && trim($scopeLocation) !== '' ? trim($scopeLocation) : null);
        }

        return $query->exists();
    }

    private function syncAlertsForImportedReport(
        int $indicatorId,
        string $startDate,
        string $endDate,
        string $projectCode,
        ?string $scopeLocation = null
    ): void {
        if (! Schema::hasTable('me_alerts') || ! Schema::hasTable('me_alert_rules')) {
            return;
        }

        try {
            $query = MeIndicatorReport::query()
                ->where('indicator_id', $indicatorId)
                ->whereDate('period_start', $startDate)
                ->whereDate('period_end', $endDate);

            if (isset($this->reportColumns['scope_project'])) {
                $query->where('scope_project', $projectCode !== '' ? $projectCode : null);
            }

            if (isset($this->reportColumns['scope_location'])) {
                $query->where('scope_location', $scopeLocation !== null && trim($scopeLocation) !== '' ? trim($scopeLocation) : null);
            }

            $report = $query->first();

            if ($report !== null) {
                app(AlertService::class)->syncForReport($report);
            }
        } catch (QueryException) {
            // Keep import flow running even when alert sync fails.
        } catch (Throwable) {
            // Keep import flow running even when alert sync fails.
        }
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
        $moneyHints = ['áŒˆá‰¢', 'á‹ˆáŒª', 'á‰¥á‹µáˆ­', 'á‰áŒ á‰£', 'á‰µáˆ­á', 'ETB', 'birr'];
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

        if (($string === '') || in_array($string, ['-', '--', 'â€”', 'â€“'], true)) {
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

    private function normalizeNullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
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
        $value = str_replace(['project', 'á•áˆ®áŒ€áŠ­á‰µ', 'svo'], ' ', (string) $value);
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

        return str_contains($payload, 'á•áˆ®áŒ€áŠ­á‰± á‰áŒ¥áˆ­')
            || str_contains($payload, 'timestamp')
            || str_contains($payload, 'column');
    }

    private function buildMetricRecordKey(
        int $indicatorId,
        string $startDate,
        string $endDate,
        ?string $scopeProject,
        ?string $scopeLocation = null
    ): string {
        $scope = $scopeProject ?? '__NULL__';
        $location = $scopeLocation !== null ? mb_strtolower(trim($scopeLocation)) : '__NULL__';

        return "{$indicatorId}|{$startDate}|{$endDate}|{$scope}|{$location}";
    }

    private function buildProjectPeriodKey(
        string $projectCode,
        string $startDate,
        string $endDate
    ): string {
        $project = mb_strtolower(trim($projectCode));

        return "{$project}|{$startDate}|{$endDate}";
    }

    private function buildProjectIndicatorPeriodKey(
        string $projectCode,
        string $indicatorName,
        string $startDate,
        string $endDate
    ): string {
        $project = mb_strtolower(trim($projectCode));
        $indicator = mb_strtolower(trim($indicatorName));

        return "{$project}|{$indicator}|{$startDate}|{$endDate}";
    }

    private function formatImportExceptionMessage(Throwable $exception): string
    {
        $message = trim(preg_replace('/\s+/u', ' ', $exception->getMessage()) ?? '');

        if ($message === '') {
            $message = 'Unexpected error while reading this row.';
        }

        if (str_contains($message, 'SQLSTATE[')) {
            return 'Database validation error while writing row data. Check rejected rows CSV/debug report for context.';
        }

        if (mb_strlen($message) > 260) {
            return mb_substr($message, 0, 257) . '...';
        }

        return $message;
    }

    private function validateDecimal142Value(
        array &$summary,
        int $row,
        string $metricLabel,
        string $valueType,
        float $value,
        string $column,
        string $projectName,
        string $projectCode,
        string $period
    ): bool {
        if (($value >= self::DECIMAL_14_2_MIN) && ($value <= self::DECIMAL_14_2_MAX)) {
            return true;
        }

        $valueText = number_format($value, 2, '.', '');
        $min = number_format(self::DECIMAL_14_2_MIN, 2, '.', '');
        $max = number_format(self::DECIMAL_14_2_MAX, 2, '.', '');
        $reason = "Metric '{$metricLabel}' {$valueType} value {$valueText} is out of allowed range ({$min} to {$max}) for {$column} (DECIMAL(14,2)).";

        $this->addWarning($summary, "Row {$row}: {$reason}");
        $this->addRejectedRow($summary, $row, $reason, $projectName, $projectCode, $period);

        return false;
    }

    private function addWarning(array &$summary, string $warning): void
    {
        if (count($summary['warnings']) < 100) {
            $summary['warnings'][] = $warning;
        }
    }

    private function addRejectedRow(
        array &$summary,
        int $row,
        string $reason,
        string $projectName,
        string $projectCode,
        string $period
    ): void {
        if (! isset($summary['rejected_rows']) || ! is_array($summary['rejected_rows'])) {
            $summary['rejected_rows'] = [];
        }

        if (count($summary['rejected_rows']) >= 1000) {
            return;
        }

        $summary['rejected_rows'][] = [
            'row' => $row,
            'project_name' => $projectName,
            'project_code' => $projectCode,
            'period' => $period,
            'reason' => $reason,
        ];
    }

    private function addAcceptedRowPreview(
        array &$summary,
        int $row,
        string $projectName,
        string $projectCode,
        string $period,
        int $metricWrites
    ): void {
        if (! isset($summary['accepted_rows_preview']) || ! is_array($summary['accepted_rows_preview'])) {
            $summary['accepted_rows_preview'] = [];
        }

        if (count($summary['accepted_rows_preview']) >= 100) {
            return;
        }

        $summary['accepted_rows_preview'][] = [
            'row' => $row,
            'project_name' => $projectName,
            'project_code' => $projectCode,
            'period' => $period,
            'metrics_written' => $metricWrites,
        ];
    }

    private function storeRejectedRowsCsv(array $rows): ?string
    {
        if ($rows === []) {
            return null;
        }

        try {
            $handle = fopen('php://temp', 'r+');

            if (! is_resource($handle)) {
                return null;
            }

            fputcsv($handle, ['row', 'project_name', 'project_code', 'period', 'reason']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['row'] ?? null,
                    $row['project_name'] ?? '',
                    $row['project_code'] ?? '',
                    $row['period'] ?? '',
                    $row['reason'] ?? '',
                ]);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            if (! is_string($csv)) {
                return null;
            }

            $dir = 'me-import-reports';
            $filename = 'weekly-import-rejected-' . now()->format('Ymd_His') . '.csv';
            $path = $dir . '/' . $filename;

            Storage::disk('local')->put($path, $csv);

            return storage_path('app/' . $path);
        } catch (Throwable) {
            return null;
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadImportHistory(): array
    {
        try {
            $disk = Storage::disk('local');
            if (! $disk->exists(self::IMPORT_HISTORY_PATH)) {
                return [];
            }

            $raw = $disk->get(self::IMPORT_HISTORY_PATH);
            if (! is_string($raw) || ($raw === '')) {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter($decoded, fn ($item): bool => is_array($item)));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findImportHistoryEntry(string $fileHash): ?array
    {
        foreach ($this->loadImportHistory() as $entry) {
            if (($entry['file_hash'] ?? null) === $fileHash) {
                return $entry;
            }
        }

        return null;
    }

    private function recordImportHistory(string $fileHash, string $fileName, array $summary): void
    {
        try {
            $history = $this->loadImportHistory();
            $history = array_values(array_filter(
                $history,
                fn (array $entry): bool => ($entry['file_hash'] ?? null) !== $fileHash
            ));

            array_unshift($history, [
                'file_hash' => $fileHash,
                'file_name' => $fileName,
                'imported_at' => now()->toDateTimeString(),
                'rows_processed' => (int) ($summary['rows_processed'] ?? 0),
                'rows_rejected' => count($summary['rejected_rows'] ?? []),
            ]);

            if (count($history) > self::IMPORT_HISTORY_LIMIT) {
                $history = array_slice($history, 0, self::IMPORT_HISTORY_LIMIT);
            }

            Storage::disk('local')->put(
                self::IMPORT_HISTORY_PATH,
                json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        } catch (Throwable) {
            // No-op: import should not fail because history logging failed.
        }
    }
}



