<?php

namespace Tests\Unit\AI;

use PHPUnit\Framework\TestCase;

/**
 * Bug Condition Exploration Test - Unit Tests
 * 
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**
 * 
 * This test verifies the bug condition detection logic:
 * - containsReportKeywords(prompt) returns true for prompts with report keywords
 * - moduleContextDetectable(prompt) returns true for prompts with module context
 * 
 * These helper methods will be used in the integration test to verify the bug exists.
 */
class ReportGenerationBugConditionTest extends TestCase
{
    /**
     * @test
     * @dataProvider reportKeywordPromptsProvider
     * 
     * Test that prompts with report keywords are correctly detected
     */
    public function test_contains_report_keywords_detection(string $prompt, bool $expected): void
    {
        $result = $this->containsReportKeywords($prompt);
        
        $this->assertEquals(
            $expected,
            $result,
            $expected 
                ? "Expected to detect report keywords in: '{$prompt}'"
                : "Expected NOT to detect report keywords in: '{$prompt}'"
        );
    }

    /**
     * @test
     * @dataProvider moduleContextPromptsProvider
     * 
     * Test that prompts with module context are correctly detected
     */
    public function test_module_context_detection(string $prompt, bool $expected): void
    {
        $result = $this->moduleContextDetectable($prompt);
        
        $this->assertEquals(
            $expected,
            $result,
            $expected 
                ? "Expected to detect module context in: '{$prompt}'"
                : "Expected NOT to detect module context in: '{$prompt}'"
        );
    }

    /**
     * @test
     * @dataProvider bugConditionPromptsProvider
     * 
     * Test that prompts triggering the bug condition are correctly identified
     */
    public function test_bug_condition_identification(
        string $prompt,
        bool $expectedBugCondition,
        string $description
    ): void {
        $hasReportKeywords = $this->containsReportKeywords($prompt);
        $hasModuleContext = $this->moduleContextDetectable($prompt);
        $isBugCondition = $hasReportKeywords && $hasModuleContext;
        
        $this->assertEquals(
            $expectedBugCondition,
            $isBugCondition,
            "Bug condition detection failed for: {$description}. " .
            "Prompt: '{$prompt}', " .
            "Has report keywords: " . ($hasReportKeywords ? 'yes' : 'no') . ", " .
            "Has module context: " . ($hasModuleContext ? 'yes' : 'no')
        );
    }

    /**
     * Data provider for report keyword detection tests
     */
    public static function reportKeywordPromptsProvider(): array
    {
        return [
            'report_keyword' => ['give me this week\'s report on recruitment', true],
            'dashboard_keyword' => ['show me procurement dashboard', true],
            'analysis_keyword' => ['analyze recruitment pipeline', true],
            'overview_keyword' => ['create an overview of hiring', true],
            'analytics_keyword' => ['show me analytics for procurement', true],
            'generate_keyword' => ['generate a report on recruitment', true],
            'simple_query' => ['How many active campaigns?', false],
            'procedural_question' => ['How do I create a campaign?', false],
            'data_request' => ['Show me the recruitment data', false],
        ];
    }

    /**
     * Data provider for module context detection tests
     */
    public static function moduleContextPromptsProvider(): array
    {
        return [
            'recruitment_module' => ['give me recruitment report', true],
            'procurement_module' => ['show me procurement dashboard', true],
            'hiring_context' => ['analyze hiring pipeline', true],
            'candidate_context' => ['candidate overview', true],
            'purchase_context' => ['purchase order analysis', true],
            'supplier_context' => ['supplier dashboard', true],
            'no_module_context' => ['How many active campaigns?', false],
            'generic_question' => ['What is the status?', false],
        ];
    }

    /**
     * Data provider for bug condition identification tests
     */
    public static function bugConditionPromptsProvider(): array
    {
        return [
            'recruitment_report' => [
                'prompt' => 'give me this week\'s report on recruitment module',
                'expectedBugCondition' => true,
                'description' => 'Recruitment report request with report keyword and module context',
            ],
            'procurement_dashboard' => [
                'prompt' => 'show me procurement dashboard',
                'expectedBugCondition' => true,
                'description' => 'Procurement dashboard request with dashboard keyword and module context',
            ],
            'recruitment_analysis' => [
                'prompt' => 'analyze recruitment pipeline',
                'expectedBugCondition' => true,
                'description' => 'Recruitment analysis request with analysis keyword and module context',
            ],
            'hiring_overview' => [
                'prompt' => 'create an overview of hiring activity',
                'expectedBugCondition' => true,
                'description' => 'Hiring overview request with overview keyword and module context',
            ],
            'simple_query_no_bug' => [
                'prompt' => 'How many active campaigns?',
                'expectedBugCondition' => false,
                'description' => 'Simple query without report keywords or module context',
            ],
            'procedural_no_bug' => [
                'prompt' => 'How do I create a campaign?',
                'expectedBugCondition' => false,
                'description' => 'Procedural question without report keywords',
            ],
            'report_without_module' => [
                'prompt' => 'give me a report',
                'expectedBugCondition' => false,
                'description' => 'Report keyword without module context',
            ],
            'module_without_report' => [
                'prompt' => 'show me recruitment data',
                'expectedBugCondition' => false,
                'description' => 'Module context without report keyword',
            ],
        ];
    }

    /**
     * Helper: Check if prompt contains report keywords
     * 
     * This implements the containsReportKeywords() function from the bug condition specification.
     */
    protected function containsReportKeywords(string $text): bool
    {
        $keywords = [
            'report',
            'dashboard',
            'analysis',
            'overview',
            'analytics',
            'analyze',
            'generate',
        ];
        
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
     * 
     * This implements the moduleContextDetectable() function from the bug condition specification.
     */
    protected function moduleContextDetectable(string $text): bool
    {
        $modules = [
            'recruitment',
            'procurement',
            'hiring',
            'candidate',
            'purchase',
            'supplier',
            'hr',
            'finance',
        ];
        
        $lowerText = strtolower($text);
        
        foreach ($modules as $module) {
            if (str_contains($lowerText, $module)) {
                return true;
            }
        }
        
        return false;
    }
}
