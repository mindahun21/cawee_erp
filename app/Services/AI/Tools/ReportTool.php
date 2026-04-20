<?php

namespace App\Services\AI\Tools;

use App\Models\User;
use App\Models\AiGeneratedReport;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Tool as PrismTool;
use Illuminate\Support\Facades\Log;

/**
 * ReportTool - Prism tool for generating structured dashboard reports
 * 
 * This tool allows the AI to create comprehensive dashboard reports with
 * KPIs, charts, tables, insights, and recommendations.
 * 
 * IMPORTANT: The AI should call get_live_data FIRST to gather data,
 * then use this tool to generate the structured report.
 */
class ReportTool
{
    /**
     * Convert this tool to a Prism Tool definition
     */
    public function asPrismTool(): PrismTool
    {
        try {
            return Tool::as('generate_report')
                ->for('Generate a structured dashboard report with KPIs, charts, tables, insights, and recommendations. IMPORTANT: You should call get_live_data FIRST to gather the necessary data, then use this tool to create the structured report based on that data. This tool creates a beautiful dashboard that users can save, export, and share.')
                ->withStringParameter(
                    'title',
                    'Report title (e.g., "Recruitment Pipeline Analysis")',
                    true
                )
                ->withStringParameter(
                    'module',
                    'Module context (recruitment, procurement, hr, finance, etc)',
                    true
                )
                ->withStringParameter(
                    'summary',
                    'Executive summary (2-4 sentences) of key findings',
                    true
                )
                ->withStringParameter(
                    'kpi_cards_json',
                    'JSON array of KPI cards. Each card: {label, value, description, trend: "up|down|neutral", color: "blue|green|red|yellow|purple"}',
                    true
                )
                ->withStringParameter(
                    'charts_json',
                    'JSON array of charts. Each chart: {title, type: "bar|doughnut|line|horizontalBar", labels: [], datasets: [{label, data: [], backgroundColor: []}]}',
                    false
                )
                ->withStringParameter(
                    'tables_json',
                    'JSON array of data tables. Each table: {title, description, columns: [{label, key}], rows: [{key: value}]}',
                    false
                )
                ->withStringParameter(
                    'insights_json',
                    'JSON array of key insights (strings). Each insight should reference specific numbers from the data.',
                    true
                )
                ->withStringParameter(
                    'recommendations_json',
                    'JSON array of recommendations. Each: {title, description, priority: "high|medium|low"}',
                    false
                )
                ->using(function (
                    string $title,
                    string $module,
                    string $summary,
                    string $kpi_cards_json,
                    ?string $charts_json = null,
                    ?string $tables_json = null,
                    string $insights_json,
                    ?string $recommendations_json = null
                ) {
                    return $this->execute(
                        $title,
                        $module,
                        $summary,
                        $kpi_cards_json,
                        $charts_json,
                        $tables_json,
                        $insights_json,
                        $recommendations_json
                    );
                });
        } catch (\Exception $e) {
            Log::error("ReportTool: Failed to create Prism tool", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return a fallback tool
            return Tool::as('generate_report_fallback')
                ->for('Generate dashboard report (currently unavailable)')
                ->withStringParameter('title', 'Report title', true)
                ->using(fn(string $title) => "Error: Report generation tool is currently unavailable. Please try again later.");
        }
    }

    /**
     * Execute the tool - generate and save the report
     */
    public function execute(
        string $title,
        string $module,
        string $summary,
        string $kpi_cards_json,
        ?string $charts_json,
        ?string $tables_json,
        string $insights_json,
        ?string $recommendations_json
    ): string {
        try {
            // Get authenticated user
            $user = auth()->user();
            if (!$user instanceof User) {
                return "Error: User not authenticated";
            }

            // Parse JSON parameters
            $kpi_cards = $this->parseJson($kpi_cards_json, 'kpi_cards');
            $charts = $charts_json ? $this->parseJson($charts_json, 'charts') : [];
            $tables = $tables_json ? $this->parseJson($tables_json, 'tables') : [];
            $insights = $this->parseJson($insights_json, 'insights');
            $recommendations = $recommendations_json ? $this->parseJson($recommendations_json, 'recommendations') : [];

            // Validate required data
            if (empty($kpi_cards)) {
                return "Error: At least one KPI card is required";
            }

            if (empty($insights)) {
                return "Error: At least one insight is required";
            }

            // Build report JSON
            $reportJson = [
                'summary' => $summary,
                'kpi_cards' => $kpi_cards,
                'charts' => $charts,
                'tables' => $tables,
                'insights' => $insights,
                'recommendations' => $recommendations,
            ];

            // Get conversation ID from session/context
            $conversationId = session('current_conversation_id');

            // Save report to database (temporary, not saved yet)
            $report = AiGeneratedReport::create([
                'user_id' => $user->id,
                'conversation_id' => $conversationId,
                'title' => $title,
                'prompt' => "Generated via AI", // Will be updated from chat context
                'report_json' => $reportJson,
                'module_context' => $module,
                'is_saved' => false,
            ]);

            Log::info("ReportTool: Report generated successfully", [
                'report_id' => $report->id,
                'user_id' => $user->id,
                'title' => $title,
                'module' => $module,
            ]);

            // Return success message with report ID
            // IMPORTANT: This response format is detected by AiRouterService to create report message type
            return "REPORT_GENERATED|{$report->id}|{$title}|Report generated successfully. Click the link below to view the full dashboard with interactive charts and insights.";

        } catch (\Exception $e) {
            Log::error("ReportTool: Error generating report", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return "Error: Failed to generate report. " . $e->getMessage();
        }
    }

    /**
     * Parse JSON string safely
     */
    protected function parseJson(string $json, string $field): array
    {
        try {
            $decoded = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("ReportTool: JSON parse error for {$field}", [
                    'error' => json_last_error_msg(),
                    'json' => substr($json, 0, 200),
                ]);
                return [];
            }

            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            Log::warning("ReportTool: Exception parsing JSON for {$field}", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
