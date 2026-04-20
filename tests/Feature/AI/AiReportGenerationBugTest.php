<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\AiGeneratedReport;
use App\Services\AI\Core\AiRouterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Bug Condition Exploration Test for AI Report Generation Fix
 * 
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**
 * 
 * This test is EXPECTED TO FAIL on unfixed code.
 * When the test fails, it confirms the bug exists (this is the SUCCESS case for exploration).
 * 
 * The bug: When users request reports using keywords like "report", "dashboard", "analysis",
 * the AI responds with conversational text claiming a report was generated, but no actual
 * report is created in the database and no clickable link appears in the chat.
 * 
 * Expected counterexamples on UNFIXED code:
 * - AI responds with conversational text claiming report was generated
 * - No AiGeneratedReport record created in database
 * - No report link appears in chat UI
 * - Response type is 'text' instead of 'report'
 */
class AiReportGenerationBugTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AiRouterService $aiRouter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['view_recruitment', 'view_procurement']);
        
        $this->actingAs($this->user);
        
        $this->aiRouter = app(AiRouterService::class);
    }

    /**
     * @test
     * @dataProvider reportRequestPromptsProvider
     * 
     * Property 1: Bug Condition - Report Generation Failure on Unfixed Code
     * 
     * For any user prompt where report keywords are detected (report, dashboard, analysis, overview)
     * AND module context is detectable (recruitment, procurement), the system SHOULD:
     * 1. Generate a report
     * 2. Save it to the ai_generated_reports table
     * 3. Return a message with type='report'
     * 4. Include report_id in metadata
     * 5. Create a report_json with required keys: summary, kpi_cards, charts, insights, recommendations
     * 
     * EXPECTED OUTCOME ON UNFIXED CODE: This test FAILS
     * - This failure confirms the bug exists
     * - The AI responds with text but doesn't create a database record
     * - No report link appears in the UI
     */
    public function test_report_generation_with_keywords_should_create_database_record(
        string $prompt,
        string $expectedModule
    ): void {
        // Arrange: Clear any existing reports
        AiGeneratedReport::query()->delete();
        $conversationId = 'test-conversation-' . uniqid();
        
        // Act: Route the prompt through AiRouterService
        $result = $this->aiRouter->route($this->user, $conversationId, $prompt);
        
        // Assert 1: Response type should be 'report'
        $this->assertEquals(
            'report',
            $result['type'],
            "Expected response type to be 'report' but got '{$result['type']}'. " .
            "This indicates the AI responded with conversational text instead of generating a report."
        );
        
        // Assert 2: Response metadata should contain report_id
        $this->assertArrayHasKey(
            'metadata',
            $result,
            "Expected response to have 'metadata' key containing report_id"
        );
        
        $this->assertArrayHasKey(
            'report_id',
            $result['metadata'] ?? [],
            "Expected response metadata to contain 'report_id'"
        );
        
        $reportId = $result['metadata']['report_id'] ?? null;
        $this->assertNotNull($reportId, "Expected report_id to be present in metadata");
        
        // Assert 3: AiGeneratedReport record should exist in database
        $report = AiGeneratedReport::find($reportId);
        $this->assertNotNull(
            $report,
            "Expected AiGeneratedReport record with ID {$reportId} to exist in database. " .
            "This is the core bug: no database record is created."
        );
        
        // Assert 4: Report should have correct fields
        $this->assertEquals($this->user->id, $report->user_id);
        $this->assertEquals($conversationId, $report->conversation_id);
        $this->assertEquals($prompt, $report->prompt);
        $this->assertEquals($expectedModule, $report->module_context);
        $this->assertFalse($report->is_saved, "Report should be temporary (is_saved=false)");
        
        // Assert 5: Report JSON should contain required keys
        $reportJson = $report->report_json;
        $this->assertIsArray($reportJson, "Expected report_json to be an array");
        
        $requiredKeys = ['summary', 'kpi_cards', 'charts', 'insights', 'recommendations'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $reportJson,
                "Expected report_json to contain '{$key}' key"
            );
        }
        
        // Assert 6: Verify report structure is valid
        $this->assertIsString($reportJson['summary'], "Summary should be a string");
        $this->assertIsArray($reportJson['kpi_cards'], "KPI cards should be an array");
        $this->assertIsArray($reportJson['charts'], "Charts should be an array");
        $this->assertIsArray($reportJson['insights'], "Insights should be an array");
        $this->assertIsArray($reportJson['recommendations'], "Recommendations should be an array");
        
        // Assert 7: Verify KPI cards structure
        $this->assertGreaterThanOrEqual(
            4,
            count($reportJson['kpi_cards']),
            "Expected at least 4 KPI cards"
        );
        
        // Assert 8: Verify charts structure
        $this->assertGreaterThanOrEqual(
            2,
            count($reportJson['charts']),
            "Expected at least 2 charts"
        );
    }

    /**
     * Data provider for report request prompts
     * 
     * Each prompt contains:
     * 1. Report keywords (report, dashboard, analysis, overview)
     * 2. Module context (recruitment, procurement)
     * 
     * These are the prompts that trigger the bug condition.
     */
    public static function reportRequestPromptsProvider(): array
    {
        return [
            'recruitment_report_request' => [
                'prompt' => 'give me this week\'s report on recruitment module',
                'expectedModule' => 'recruitment',
            ],
            'procurement_dashboard_request' => [
                'prompt' => 'show me procurement dashboard',
                'expectedModule' => 'procurement',
            ],
            'recruitment_analysis_request' => [
                'prompt' => 'analyze recruitment pipeline',
                'expectedModule' => 'recruitment',
            ],
            'hiring_overview_request' => [
                'prompt' => 'create an overview of hiring activity',
                'expectedModule' => 'recruitment',
            ],
        ];
    }

    /**
     * @test
     * 
     * Helper test to verify bug condition detection logic
     * 
     * This test verifies that the prompts we're testing actually contain:
     * 1. Report keywords (report, dashboard, analysis, overview)
     * 2. Module context (recruitment, procurement)
     */
    public function test_bug_condition_detection_helpers(): void
    {
        // Test containsReportKeywords
        $this->assertTrue(
            $this->containsReportKeywords('give me this week\'s report on recruitment'),
            "Should detect 'report' keyword"
        );
        $this->assertTrue(
            $this->containsReportKeywords('show me procurement dashboard'),
            "Should detect 'dashboard' keyword"
        );
        $this->assertTrue(
            $this->containsReportKeywords('analyze recruitment pipeline'),
            "Should detect 'analyze' keyword"
        );
        $this->assertTrue(
            $this->containsReportKeywords('create an overview of hiring'),
            "Should detect 'overview' keyword"
        );
        $this->assertFalse(
            $this->containsReportKeywords('How many active campaigns?'),
            "Should not detect report keywords in simple query"
        );
        
        // Test moduleContextDetectable
        $this->assertTrue(
            $this->moduleContextDetectable('give me recruitment report'),
            "Should detect 'recruitment' module"
        );
        $this->assertTrue(
            $this->moduleContextDetectable('show me procurement dashboard'),
            "Should detect 'procurement' module"
        );
        $this->assertTrue(
            $this->moduleContextDetectable('analyze hiring pipeline'),
            "Should detect 'hiring' as recruitment context"
        );
        $this->assertTrue(
            $this->moduleContextDetectable('candidate overview'),
            "Should detect 'candidate' as recruitment context"
        );
        $this->assertFalse(
            $this->moduleContextDetectable('How many active campaigns?'),
            "Should not detect module context in simple query"
        );
    }

    /**
     * Helper: Check if prompt contains report keywords
     */
    protected function containsReportKeywords(string $text): bool
    {
        $keywords = ['report', 'dashboard', 'analysis', 'overview', 'analytics'];
        $lowerText = strtolower($text);
        
        foreach ($keywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Helper: Check if prompt contains module context
     */
    protected function moduleContextDetectable(string $text): bool
    {
        $modules = ['recruitment', 'procurement', 'hiring', 'candidate', 'purchase', 'supplier'];
        $lowerText = strtolower($text);
        
        foreach ($modules as $module) {
            if (str_contains($lowerText, $module)) {
                return true;
            }
        }
        
        return false;
    }
}
