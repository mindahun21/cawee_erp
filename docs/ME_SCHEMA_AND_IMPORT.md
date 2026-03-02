# M&E Module Guideline (Complete)

This guide explains the full M&E module: what it stores, how data flows, where to find it in UI, and how weekly import maps Excel columns to database records.

## 1) Module Scope

The M&E module handles:
- project-level indicator definition
- weekly/baseline/midline/endline reporting periods
- planned targets vs actual results
- disaggregation
- alerts and thresholds
- survey capture and mapping to indicators
- audit trail and beneficiary feedback
- dashboard summaries/charts/tables

## 2) Main UI Areas (Filament)

Navigation group: `M&E`
- `Indicators`
- `Reports`
- `Alerts`
- `Surveys`
- `Disaggregation`
- `Beneficiary Feedback`
- `M&E Dashboard`
- `Import Weekly Report`

Important: `Targets` is currently inside each Indicator as a relation manager, not a separate sidebar resource.

## 3) Core Database Model

### 3.1 Projects and periods
`me_projects`
- Project registry (`name`, `project_code`, timeline fields).

`me_reporting_periods`
- Period windows by project (`weekly`, `baseline`, `midline`, `endline`).
- Carries `start_date`, `end_date`, `label`, `is_locked`.

### 3.2 Indicator framework
`me_indicators`
- Indicator master (`code`, `name`, optional `name_local`).
- Framework classification (`output`, `outcome`, `impact`).
- Measurement metadata (`unit`, `frequency`, `direction`, `data_type`).
- Monitoring controls (`is_active`, thresholds, disaggregation requirement).

### 3.3 Targets and actuals
`me_indicator_targets`
- Planned values per indicator and period/date scope.
- Primary value: `target_value`.
- Optional scope: `scope_project`, `scope_location`.

`me_indicator_reports`
- Actual values per indicator and period/date scope.
- Primary value: `actual_value` (and optional `actual_text`).
- Provenance fields: `source`, `entered_by`, `entered_at`, `comment`, `notes`.

### 3.4 Disaggregation
`me_disaggregation_categories`
- Dimension definition (Gender, Age Group, Location, Disability, etc.).

`me_disaggregation_options`
- Values under each category (Female, Male, 15-19, etc.).

`me_indicator_disaggregation`
- Which disaggregation dimensions are enabled for an indicator.

`me_report_disaggregation_values`
- Actual report values split by option.

`me_indicator_target_disaggregations`
- Target values split by option.

### 3.5 Alerts
`me_alert_rules`
- Rule definitions and thresholds (warning/critical or rule-based styles).

`me_alerts`
- Generated alert entries with lifecycle (`open`, `acknowledged`, `resolved`).

### 3.6 Surveys
`me_surveys`
- Instrument metadata and survey type.

`me_survey_questions`
- Question bank per survey with optional indicator mapping.

`me_survey_responses`
- Submission-level records with source and context.

`me_survey_answers`
- Answer payload per question/response.

### 3.7 Audit and feedback
`me_audit_logs`
- Change tracking for M&E entities.

`me_beneficiary_feedback`
- Beneficiary sentiment/rating/comments with optional project/period/location linkage.

`me_locations`
- Optional location tree for geo/context.

## 4) Data Flow (How Values Move)

### 4.1 Manual entry flow
1. Create indicator in `Indicators`.
2. Add target rows in indicator `Targets` relation.
3. Add actual rows in indicator `Reports` relation or `Reports` resource.
4. Dashboard computes progress using latest matching target/report.

### 4.2 Import flow (weekly Excel)
Page: `M&E -> Import Weekly Report`  
Service: `App\Services\ME\WeeklyReportImportService`

Processing:
1. Read first sheet.
2. Detect core columns by header names.
3. Detect metric columns by plan/actual semantics.
4. Resolve/create project and reporting period.
5. Resolve/create indicator per metric label.
6. Upsert plan values to `me_indicator_targets`.
7. Upsert actual values to `me_indicator_reports` (`source=import`).
8. Save debug JSON summary under `storage/app/me-import-reports/`.

## 5) Header Rules For Import

Accepted core header aliases:
- `Timestamp`
- `Project Name`
- `Project Code` or `Project Number`
- `Reporting Period Covered` / `Reporting Period` / `Period`
- `Additional Comment` / `Comment` / `Notes` (optional)

Metric headers:
- plan side must include words like `Planned`, `Plan`, `Target` (or Amharic equivalents)
- actual side must include words like `Actual`, `Achieved` (or Amharic equivalents)

Section labels like `SOH Project Only` / `For All Projects` are ignored as separators.

## 6) Blank/Null Behavior

- Blank metric cells are allowed.
- If plan is blank and actual has numeric value: actual is imported.
- If actual is blank and plan has numeric value: plan is imported.
- If both plan and actual are blank for a metric in a row: that metric is skipped for that row.
- A row can be skipped when period cannot be parsed or no usable numeric metric value exists.

## 7) Why Some Excel Columns Are Not Visible One-by-One

The system stores normalized records, not a wide Excel replica.
- Excel metric columns become indicator target/report rows.
- Dashboard shows aggregated/filtered performance rows.
- Some tables paginate and show subsets, not every metric at once.

## 8) Observer and Alert Sync

Observer: `App\Observers\ME\MeIndicatorReportObserver`
- On report save, it tries alert synchronization.
- It safely avoids breaking import when alert schema is unavailable.

## 9) Operational Notes

- Re-import is idempotent for matching keys (updates existing rows instead of pure duplication).
- Import generates debug summary JSON to support troubleshooting.
- For consistent results, keep the approved template headers unchanged.

## 10) Quick Validation Checklist

Before import:
1. Confirm required headers exist.
2. Confirm period text is parseable.
3. Confirm numeric cells are numeric (not text with unsupported symbols).

After import:
1. Check `Indicators` count increased as expected.
2. Open an indicator and verify `Targets` + `Reports` relations.
3. Verify `Reports` resource and `M&E Dashboard` show recent data.
4. Review debug JSON file path shown in import completion notification.
