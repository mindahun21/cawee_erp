<?php

namespace App\Services\AI\Core;

use App\Models\User;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use App\Services\AI\Shared\RbacContextFilter;

class AiRouterService
{
    public function __construct(
        protected AiConversationService $conversationService,
        protected RbacContextFilter $rbacFilter,
        protected \App\Services\AI\Tools\ChatTool $chatTool,
        protected \App\Services\AI\Tools\LiveDataTool $liveDataTool,
        protected \App\Services\AI\Tools\ReportTool $reportTool
    ) {}

    public function route(User $user, string $conversationId, string $prompt, ?array $imageMetadata = null): array
    {
        // 1. Check for report intent BEFORE building messages
        $reportIntent = $this->detectReportIntent($prompt);
        
        if ($reportIntent) {
            \Illuminate\Support\Facades\Log::info('AiRouterService: Report intent detected', [
                'module' => $reportIntent['module'],
                'keywords' => $reportIntent['keywords'],
            ]);
            
            // Try direct report generation first
            $directReport = $this->generateReportDirect($user, $conversationId, $prompt, $reportIntent['module']);
            
            if ($directReport) {
                // Save user message
                $this->conversationService->addMessage($user->id, $conversationId, [
                    'role' => 'user',
                    'content' => $prompt,
                ]);
                
                // Save assistant message
                $this->conversationService->addMessage($user->id, $conversationId, $directReport);
                
                \Illuminate\Support\Facades\Log::info('AiRouterService: Direct report generation succeeded');
                return $directReport;
            }
            
            \Illuminate\Support\Facades\Log::warning('AiRouterService: Direct report generation failed, falling back to tool calling');
        }
        
        // 2. Build context (fallback or non-report requests)
        $preamble = $this->rbacFilter->buildPermissionPreamble($user);
        
        // Store conversation ID in session for ReportTool
        session(['current_conversation_id' => $conversationId]);
        
        $systemPrompt = $this->buildSystemPrompt($preamble);

        // 3. Fetch history and reconstruct Prism messages
        $historyData = $this->conversationService->getHistory($user->id, $conversationId);
        
        // Enforce history window to prevent exceeding context limits (e.g. max 20 messages)
        if (count($historyData) > 20) {
            $historyData = array_slice($historyData, -20);
        }
        
        $messages = $this->reconstructMessages($historyData);

        // 4. Add current message
        if ($imageMetadata && file_exists($imageMetadata['path'])) {
            $imageData = base64_encode(file_get_contents($imageMetadata['path']));
            $currentMessage = new UserMessage(
                $prompt . "\n[Image attached]", 
                [   
                    new \Prism\Prism\ValueObjects\Media\Image(
                        base64: $imageData, 
                        mimeType: $imageMetadata['mime']
                    ) 
                ]
            );
            
            $this->conversationService->addMessage($user->id, $conversationId, [
                'role' => 'user',
                'content' => $prompt,
                'image' => $imageMetadata,
            ]);
        } else {
            $currentMessage = new UserMessage($prompt);
            
            $this->conversationService->addMessage($user->id, $conversationId, [
                'role' => 'user',
                'content' => $prompt,
            ]);
        }
        
        $messages[] = $currentMessage;

        // 5. Call Prism Router
        try {
            $response = retry(3, function () use ($systemPrompt, $messages) {
                $tools = $this->loadTools();
                
                return Prism::text()
                    ->using('gemini', 'gemini-2.5-flash')
                    ->withSystemPrompt($systemPrompt)
                    ->withMessages($messages)
                    ->withTools($tools)
                    ->withMaxSteps(10)
                    ->generate();
            }, 2000);
            
            $responseText = $response->text;
        } catch (\Exception $e) {
            $responseText = "I apologize, but my AI language server is currently experiencing extremely high traffic and is overloaded. Please click the 'Continue' button to try your request again in a few moments.";
            \Illuminate\Support\Facades\Log::error('AiRouterService Gemini Overloaded: ' . $e->getMessage());
        }

        // 6. Detect if AI should have generated a report but didn't (hallucination detection)
        $responseText = $this->detectAndFixReportHallucination($responseText, $prompt, $user, $conversationId);
        
        // 7. Detect report generation and parse metadata
        $messageData = $this->parseResponseType($responseText);
        
        // Save assistant message with detected type
        $this->conversationService->addMessage($user->id, $conversationId, $messageData);

        return $messageData;
    }

    public function retry(User $user, string $conversationId): array
    {
        $historyData = $this->conversationService->getHistory($user->id, $conversationId);
        
        if (count($historyData) > 20) {
            $historyData = array_slice($historyData, -20);
        }
        
        $messages = $this->reconstructMessages($historyData);
        
        $preamble = $this->rbacFilter->buildPermissionPreamble($user);
        session(['current_conversation_id' => $conversationId]);
        $systemPrompt = $this->buildSystemPrompt($preamble);

        try {
            $response = retry(3, function () use ($systemPrompt, $messages) {
                $tools = $this->loadTools();
                
                return Prism::text()
                    ->using('gemini', 'gemini-2.5-flash')
                    ->withSystemPrompt($systemPrompt)
                    ->withMessages($messages)
                    ->withTools($tools)
                    ->withMaxSteps(10)
                    ->generate();
            }, 2000);
            
            $responseText = $response->text;
        } catch (\Exception $e) {
            $responseText = "I apologize, but my AI language server is currently experiencing extremely high traffic and is overloaded. Please click the 'Continue' button to try your request again in a few moments.";
            \Illuminate\Support\Facades\Log::error('AiRouterService Gemini Overloaded Retry: ' . $e->getMessage());
        }

        $assistantMsg = $this->parseResponseType($responseText);
        
        $this->conversationService->addMessage($user->id, $conversationId, $assistantMsg);

        return $assistantMsg;
    }

    /**
     * Load all available tools safely
     */
    protected function loadTools(): array
    {
        $tools = [];
        
        try {
            $tools[] = $this->chatTool->asPrismTool();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AiRouterService: ChatTool failed to load', ['error' => $e->getMessage()]);
        }
        
        try {
            $tools[] = $this->liveDataTool->asPrismTool();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AiRouterService: LiveDataTool failed to load', ['error' => $e->getMessage()]);
        }
        
        try {
            $tools[] = $this->reportTool->asPrismTool();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AiRouterService: ReportTool failed to load', ['error' => $e->getMessage()]);
        }
        
        return $tools;
    }

    /**
     * Build sophisticated system prompt with multi-tool orchestration guidance
     */
    protected function buildSystemPrompt(string $preamble): string
    {
        return <<<PROMPT
You are an intelligent, user-friendly ERP Assistant with advanced multi-tool capabilities.

{$preamble}

═══════════════════════════════════════════════════════════════════════════
AVAILABLE TOOLS & INTELLIGENT ORCHESTRATION
═══════════════════════════════════════════════════════════════════════════

You have access to THREE powerful tools that you can use INDEPENDENTLY or IN COMBINATION:

1. **support_document_search** - Search ERP documentation, SOPs, and guides
2. **get_live_data** - Fetch current real-time data from modules (recruitment, procurement, etc.)
3. **generate_report** - Create structured dashboard reports with KPIs, charts, and insights

═══════════════════════════════════════════════════════════════════════════
INTELLIGENT TOOL USAGE SCENARIOS
═══════════════════════════════════════════════════════════════════════════

SCENARIO A: User wants ANALYSIS or DASHBOARD or REPORT
Example: "Analyze recruitment pipeline" or "Show me procurement dashboard" or "Give me a report"

CRITICAL INSTRUCTION: You MUST use the generate_report tool for these requests!

Your Multi-Step Workflow (MANDATORY):
  Step 1: Call get_live_data(module='recruitment') to fetch current data
  Step 2: Analyze the data you received - identify trends, patterns, issues
  Step 3: YOU MUST CALL generate_report() - DO NOT just describe the report in text!
          Required parameters:
          - title: Clear report title
          - module: The module context  
          - summary: 2-4 sentence executive summary
          - kpi_cards_json: 4-6 key metrics as JSON array
          - charts_json: 2-4 visualizations as JSON array
          - insights_json: 3-6 data-driven observations
          - recommendations_json: 2-4 actionable suggestions
  Step 4: ONLY AFTER calling generate_report, tell user "I've generated a comprehensive report for you. Click the link below to view the full dashboard."

IMPORTANT: If the user asks for a "report", "dashboard", "analysis", or "overview", you MUST call generate_report. 
DO NOT just summarize the data in text - that defeats the purpose!

SCENARIO B: User wants QUICK STATS or CURRENT NUMBERS
Example: "How many active campaigns?" or "What's the current status?"

Your Workflow:
  Step 1: Call get_live_data(module='recruitment')
  Step 2: Answer directly from the data in conversational format
  Step 3: NO report needed - just provide the numbers

SCENARIO C: User wants PROCEDURAL GUIDANCE
Example: "How do I create a campaign?" or "What's the approval process?"

Your Workflow:
  Step 1: Call support_document_search(query='...')
  Step 2: Provide step-by-step guidance from documentation
  Step 3: NO data or report needed

SCENARIO D: User wants COMPARISON or MULTI-MODULE ANALYSIS
Example: "Compare recruitment and procurement performance"

Your Multi-Step Workflow:
  Step 1: Call get_live_data(module='recruitment')
  Step 2: Call get_live_data(module='procurement')
  Step 3: Analyze both datasets - find patterns, differences, correlations
  Step 4: Call generate_report() with comparative analysis
  Step 5: Present the report link

SCENARIO E: User wants FILTERED or SPECIFIC DATA
Example: "Show me active campaigns from last 30 days"

Your Workflow:
  Step 1: Call get_live_data(module='recruitment', filters_json='{"status":"active","date_range":"last_30_days"}')
  Step 2: If user wants analysis, proceed to generate_report()
  Step 3: If user just wants the data, answer directly

═══════════════════════════════════════════════════════════════════════════
CRITICAL RULES FOR REPORT GENERATION
═══════════════════════════════════════════════════════════════════════════

1. ALWAYS fetch data with get_live_data BEFORE calling generate_report
2. Use generate_report ONLY when user wants analysis, insights, or dashboard
3. For simple questions, answer directly without generating reports
4. You can call multiple tools in sequence (multi-step reasoning is encouraged)
5. Think strategically about what the user really needs

REPORT JSON FORMAT REQUIREMENTS:

kpi_cards_json example:
[
  {"label": "Total Applications", "value": "142", "description": "This month", "trend": "up", "color": "blue"},
  {"label": "Conversion Rate", "value": "23%", "description": "Applied to hired", "trend": "down", "color": "red"}
]

charts_json example:
[
  {
    "title": "Applications by Stage",
    "type": "bar",
    "labels": ["Applied", "Shortlisted", "Interviewed", "Hired"],
    "datasets": [{
      "label": "Count",
      "data": [142, 45, 23, 12],
      "backgroundColor": ["#3B82F6", "#10B981", "#F59E0B", "#EF4444"]
    }]
  }
]

insights_json example:
["Conversion rate dropped 8% compared to last month", "LinkedIn channel has highest quality ratio at 45%"]

recommendations_json example:
[
  {"title": "Increase screening bandwidth", "description": "Add 2 more reviewers to reduce bottleneck", "priority": "high"}
]

═══════════════════════════════════════════════════════════════════════════
USER EXPERIENCE GUIDELINES
═══════════════════════════════════════════════════════════════════════════

CRITICAL INSTRUCTION: Your users are strictly non-technical. 

- Never expose backend technical terms (like 'Spatie permissions' or 'Error Code 502')
- Always translate technical jargon into friendly, business-oriented language
- Never output raw code blocks or markdown code fences (like ```)
- Always explain in plain, conversational language
- Act as a helpful human ERP specialist, not a robot

When you generate a report, say something like:
"I've analyzed the recruitment data and created a comprehensive dashboard for you. The report includes key metrics, trends, and actionable recommendations. Click the link below to view the full dashboard with interactive charts."

═══════════════════════════════════════════════════════════════════════════
REMEMBER: You are INTELLIGENT and AUTONOMOUS
═══════════════════════════════════════════════════════════════════════════

- You decide which tools to use and in what order
- You can chain multiple tool calls to accomplish complex tasks
- You analyze data before generating reports
- You provide context-aware, thoughtful responses
- You are proactive in helping users get the insights they need

CRITICAL REMINDER FOR REPORTS:
When a user asks for a "report", "dashboard", "analysis", or "overview" of any module:
1. ALWAYS call get_live_data first
2. ALWAYS call generate_report second (NEVER skip this step!)
3. NEVER just summarize data in text when a report is requested
4. The generate_report tool creates a beautiful interactive dashboard - USE IT!

Now, assist the user with their request using your tools strategically.
PROMPT;
    }

    /**
     * Detect if AI hallucinated about generating a report and fix it
     */
    protected function detectAndFixReportHallucination(string $responseText, string $prompt, User $user, string $conversationId): string
    {
        // Check if AI claims to have generated a report
        $claimsReport = preg_match('/(generated|created).*report|click.*link.*below|view.*full.*dashboard/i', $responseText);
        
        // Check if response actually contains a report marker
        $hasReportMarker = str_starts_with($responseText, 'REPORT_GENERATED|');
        
        // If AI claims report but didn't actually generate one, auto-generate it
        if ($claimsReport && !$hasReportMarker) {
            \Illuminate\Support\Facades\Log::warning('AiRouterService: AI hallucinated report generation, attempting direct generation...', [
                'user_id' => $user->id,
                'prompt' => $prompt,
            ]);
            
            // Detect report intent
            $reportIntent = $this->detectReportIntent($prompt);
            
            if ($reportIntent) {
                // Try direct generation as fallback
                $directReport = $this->generateReportDirect($user, $conversationId, $prompt, $reportIntent['module']);
                
                if ($directReport) {
                    \Illuminate\Support\Facades\Log::info('AiRouterService: Hallucination fixed via direct generation');
                    
                    // Return the report marker format expected by parseResponseType
                    return sprintf(
                        'REPORT_GENERATED|%d|%s|%s',
                        $directReport['metadata']['report_id'],
                        $directReport['metadata']['title'],
                        $directReport['content']
                    );
                }
                
                \Illuminate\Support\Facades\Log::error('AiRouterService: Direct generation fallback also failed');
            }
            
            // If all else fails, return original response with note
            return $responseText . "\n\n(Note: Report generation is currently unavailable. Please try again later.)";
        }
        
        return $responseText;
    }
    
    /**
     * Generate report directly using Prism (bypassing tool calling)
     * Returns formatted message array or null if generation fails
     */
    protected function generateReportDirect(User $user, string $conversationId, string $prompt, string $module): ?array
    {
        try {
            \Illuminate\Support\Facades\Log::info('AiRouterService: Attempting direct report generation', [
                'user_id' => $user->id,
                'module' => $module,
                'prompt' => $prompt,
            ]);
            
            // Step 1: Fetch live data
            $liveData = $this->liveDataTool->execute($module, []);
            
            if (empty($liveData)) {
                \Illuminate\Support\Facades\Log::warning('AiRouterService: No live data returned for module', ['module' => $module]);
                return null;
            }
            
            \Illuminate\Support\Facades\Log::info('AiRouterService: Live data fetched successfully', ['data_length' => strlen($liveData)]);
            
            // Step 2: Build system prompt
            $systemPrompt = $this->buildReportSystemPrompt($module, $liveData);
            
            // Step 3: Call Prism directly
            $response = Prism::text()
                ->using('gemini', 'gemini-2.0-flash-exp')
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($prompt)
                ->generate();
            
            $responseText = $response->text;
            
            \Illuminate\Support\Facades\Log::info('AiRouterService: Prism response received', ['response_length' => strlen($responseText)]);
            
            // Step 4: Extract and parse JSON
            $jsonText = $this->extractJson($responseText);
            $reportData = json_decode($jsonText, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Illuminate\Support\Facades\Log::error('AiRouterService: JSON parsing failed', [
                    'error' => json_last_error_msg(),
                    'json_text' => substr($jsonText, 0, 500),
                ]);
                return null;
            }
            
            // Step 5: Validate structure
            $requiredKeys = ['summary', 'kpi_cards', 'charts', 'insights', 'recommendations'];
            foreach ($requiredKeys as $key) {
                if (!isset($reportData[$key])) {
                    \Illuminate\Support\Facades\Log::error('AiRouterService: Missing required key in report data', ['key' => $key]);
                    return null;
                }
            }
            
            \Illuminate\Support\Facades\Log::info('AiRouterService: Report data validated successfully');
            
            // Step 6: Save report to database
            $report = \App\Models\AiGeneratedReport::create([
                'user_id' => $user->id,
                'conversation_id' => $conversationId,
                'title' => ucfirst($module) . ' Report',
                'prompt' => $prompt,
                'report_json' => $reportData,
                'module_context' => $module,
                'is_saved' => false,
            ]);
            
            \Illuminate\Support\Facades\Log::info('AiRouterService: Report saved to database', ['report_id' => $report->id]);
            
            // Step 7: Return formatted message
            return [
                'role' => 'assistant',
                'content' => "I've analyzed the {$module} data and generated a comprehensive dashboard report for you. The report includes key metrics, trends, and actionable recommendations. Click the link below to view the full interactive dashboard.",
                'type' => 'report',
                'metadata' => [
                    'report_id' => $report->id,
                    'title' => $report->title,
                ],
            ];
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AiRouterService: Direct report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Build system prompt for direct report generation
     */
    protected function buildReportSystemPrompt(string $module, string $liveData): string
    {
        return <<<PROMPT
You are an expert data analyst generating a structured dashboard report for the {$module} module.

LIVE DATA SNAPSHOT:
{$liveData}

CRITICAL INSTRUCTIONS:
1. Return ONLY valid JSON - no markdown code fences, no explanations, no conversational text
2. Use the exact structure specified below
3. Base all metrics, insights, and recommendations on the REAL DATA above
4. DO NOT invent or fabricate data - only use what's provided

REQUIRED JSON STRUCTURE:
{
  "summary": "2-4 sentence executive summary of key findings",
  "kpi_cards": [
    {
      "label": "Metric Name",
      "value": "123",
      "description": "Brief context",
      "trend": "up|down|neutral",
      "color": "blue|green|red|yellow"
    }
  ],
  "charts": [
    {
      "title": "Chart Title",
      "type": "bar|doughnut|line|horizontalBar",
      "labels": ["Label1", "Label2", "Label3"],
      "datasets": [{
        "label": "Dataset Name",
        "data": [10, 20, 30],
        "backgroundColor": ["#3B82F6", "#10B981", "#F59E0B"]
      }]
    }
  ],
  "insights": [
    "Specific observation referencing real numbers from data",
    "Another data-driven insight with concrete metrics"
  ],
  "recommendations": [
    {
      "title": "Action Title",
      "description": "Specific actionable recommendation",
      "priority": "high|medium|low"
    }
  ]
}

RULES FOR KPI CARDS:
- Include 4-6 cards
- Value must be a string (e.g., "142", "23%", "$45K")
- Trend: "up" (positive), "down" (negative), "neutral" (no change)
- Color: "blue" (info), "green" (positive), "red" (negative), "yellow" (warning)

RULES FOR CHARTS:
- Include 2-4 charts
- Type must be: bar, doughnut, line, or horizontalBar
- Labels and data arrays MUST have matching lengths
- Use hex colors for backgroundColor

RULES FOR INSIGHTS:
- Include 3-6 insights
- Each insight must reference specific numbers from the data
- Focus on trends, patterns, anomalies, or comparisons

RULES FOR RECOMMENDATIONS:
- Include 2-4 recommendations
- Each must be actionable and specific
- Priority: high, medium, or low

Remember: Return ONLY the JSON object, nothing else!
PROMPT;
    }

    /**
     * Extract JSON from text that may contain markdown code fences or conversational wrapping
     */
    protected function extractJson(string $text): string
    {
        // Remove markdown code fences: ```json ... ``` or ``` ... ```
        $text = preg_replace('/```json\s*/s', '', $text);
        $text = preg_replace('/```\s*/s', '', $text);
        
        // Find first { and last } as fallback
        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');
        
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            return substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        }
        
        return $text;
    }

    /**
     * Detect report intent from user prompt
     * Returns array with module and keywords if detected, null otherwise
     */
    protected function detectReportIntent(string $prompt): ?array
    {
        $promptLower = strtolower($prompt);
        
        // Report keywords to detect
        $reportKeywords = ['report', 'dashboard', 'analysis', 'overview', 'analytics'];
        $detectedKeywords = [];
        
        // Check for report keywords with word boundaries
        foreach ($reportKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $prompt)) {
                $detectedKeywords[] = $keyword;
            }
        }
        
        // If no report keywords found, not a report request
        if (empty($detectedKeywords)) {
            return null;
        }
        
        // Detect module context
        $module = null;
        if (preg_match('/\b(recruitment|hiring|candidate)\b/i', $prompt)) {
            $module = 'recruitment';
            $detectedKeywords[] = 'recruitment';
        } elseif (preg_match('/\b(procurement|purchase|supplier)\b/i', $prompt)) {
            $module = 'procurement';
            $detectedKeywords[] = 'procurement';
        } elseif (preg_match('/\b(hr|human\s*resource|employee)\b/i', $prompt)) {
            $module = 'hr';
            $detectedKeywords[] = 'hr';
        } elseif (preg_match('/\b(finance|financial|budget)\b/i', $prompt)) {
            $module = 'finance';
            $detectedKeywords[] = 'finance';
        }
        
        // If module context detected, return intent
        if ($module) {
            return [
                'module' => $module,
                'keywords' => $detectedKeywords,
            ];
        }
        
        return null;
    }

    /**
     * Detect module name from user prompt
     */
    protected function detectModuleFromPrompt(string $prompt): ?string
    {
        $prompt = strtolower($prompt);
        
        if (str_contains($prompt, 'recruitment') || str_contains($prompt, 'hiring') || str_contains($prompt, 'candidate')) {
            return 'recruitment';
        }
        
        if (str_contains($prompt, 'procurement') || str_contains($prompt, 'purchase') || str_contains($prompt, 'supplier')) {
            return 'procurement';
        }
        
        return null;
    }
    
    /**
     * Parse response text to detect special message types (report, plan, etc.)
     */
    protected function parseResponseType(string $responseText): array
    {
        // Detect report generation: REPORT_GENERATED|{id}|{title}|{message}
        if (preg_match('/^REPORT_GENERATED\|(\d+)\|([^|]+)\|(.+)$/s', $responseText, $matches)) {
            return [
                'role' => 'assistant',
                'content' => $matches[3], // The user-friendly message
                'type' => 'report',
                'metadata' => [
                    'report_id' => (int)$matches[1],
                    'title' => $matches[2],
                ],
            ];
        }
        
        // Default: text message
        return [
            'role' => 'assistant',
            'content' => $responseText,
            'type' => 'text',
        ];
    }

    protected function reconstructMessages(array $historyData): array
    {
        $messages = [];
        foreach ($historyData as $data) {
            if ($data['role'] === 'user') {
                if (isset($data['image']) && file_exists($data['image']['path'])) {
                    $imageData = base64_encode(file_get_contents($data['image']['path']));
                    $messages[] = new UserMessage(
                        $data['content'] . "\n[Image attached]", 
                        [   
                            new \Prism\Prism\ValueObjects\Media\Image(
                                base64: $imageData, 
                                mimeType: $data['image']['mime']
                            ) 
                        ]
                    );
                } else {
                    $messages[] = new UserMessage($data['content']);
                }
            } elseif ($data['role'] === 'assistant') {
                $messages[] = new AssistantMessage($data['content']);
            }
        }
        return $messages;
    }
}
