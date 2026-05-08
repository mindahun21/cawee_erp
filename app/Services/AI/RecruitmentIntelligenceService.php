<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class RecruitmentIntelligenceService
{
    protected RecruitmentDataPreloader $preloader;

    public function __construct(RecruitmentDataPreloader $preloader)
    {
        $this->preloader = $preloader;
    }

    /**
     * Run an analysis query against the recruitment data.
     *
     * @return array{success: bool, report: array|null, error: string|null}
     */
    public function analyze(string $question): array
    {
        try {
            $systemPrompt = $this->buildSystemPrompt();
            $provider = $this->resolveProvider();
            $model = config('ai.model', 'gemini-2.5-flash');

            $response = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($question)
                ->withMaxTokens(8192)
                ->usingTemperature(0.3)
                ->withClientOptions(['timeout' => config('ai.timeout', 120)])
                ->asText();

            $text = $response->text;

            // Extract JSON from the response (handle markdown code fences)
            $json = $this->extractJson($text);
            $report = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('AI Report: JSON parse error', [
                    'error' => json_last_error_msg(),
                    'raw' => mb_substr($text, 0, 500),
                ]);
                return [
                    'success' => false,
                    'report'  => null,
                    'error'   => 'The AI response could not be parsed. Please try again.',
                ];
            }

            return [
                'success' => true,
                'report'  => $report,
                'error'   => null,
            ];

        } catch (\Throwable $e) {
            Log::error('AI Report: request failed', [
                'message' => $e->getMessage(),
                'trace'   => mb_substr($e->getTraceAsString(), 0, 1000),
            ]);
            return [
                'success' => false,
                'report'  => null,
                'error'   => 'AI analysis failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve the Prism Provider enum from config.
     */
    protected function resolveProvider(): Provider
    {
        $name = config('ai.provider', 'gemini');

        return match (strtolower($name)) {
            'gemini'    => Provider::Gemini,
            'openai'    => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            default     => Provider::Gemini,
        };
    }

    /**
     * Build the complete system prompt with schema context, business rules, and live data.
     */
    protected function buildSystemPrompt(): string
    {
        $schema   = $this->loadContextFile('schema.txt');
        $usecases = $this->loadContextFile('usecases.txt');
        $liveData = $this->preloader->snapshot();

        return <<<PROMPT
You are a senior Recruitment Analytics Expert embedded inside the Cawee ERP system.
Your role is to analyze recruitment data and produce a structured report dashboard.

═══════════════════════════════════════════════════
SECTION 1: DATABASE SCHEMA
═══════════════════════════════════════════════════
{$schema}

═══════════════════════════════════════════════════
SECTION 2: BUSINESS RULES & STATE MACHINES
═══════════════════════════════════════════════════
{$usecases}

═══════════════════════════════════════════════════
SECTION 3: LIVE DATA SNAPSHOT
═══════════════════════════════════════════════════
{$liveData}

═══════════════════════════════════════════════════
SECTION 4: RESPONSE INSTRUCTIONS (CRITICAL)
═══════════════════════════════════════════════════

You MUST respond with ONLY a valid JSON object (no markdown, no code fences, no explanations outside the JSON).

The JSON MUST follow this exact structure:

{
  "summary": "A 2-4 sentence executive summary of the overall recruitment health based on the data",

  "kpi_cards": [
    {
      "label": "Card Title",
      "value": "123",
      "description": "Brief context",
      "trend": "up|down|neutral",
      "color": "blue|green|red|yellow|purple|indigo"
    }
  ],

  "charts": [
    {
      "title": "Chart Title",
      "type": "bar|doughnut|line|horizontalBar",
      "labels": ["Label1", "Label2"],
      "datasets": [
        {
          "label": "Dataset Name",
          "data": [10, 20],
          "backgroundColor": ["#3B82F6", "#10B981"]
        }
      ]
    }
  ],

  "tables": [
    {
      "title": "Table Title",
      "description": "Brief description of what this table shows",
      "columns": [
        {
          "label": "Column Header",
          "key": "column_key"
        }
      ],
      "rows": [
        {
          "column_key": "Cell value",
          "another_key": "Another value"
        }
      ]
    }
  ],

  "insights": [
    "Observation sentence 1 based on the data.",
    "Observation sentence 2 based on the data."
  ],

  "recommendations": [
    {
      "title": "Short action title",
      "description": "Explanation of what to do and why",
      "priority": "high|medium|low"
    }
  ]
}

RULES:
1. Produce 4-6 KPI cards. Each "value" must be a string (e.g. "42", "68%", "3.2 days").
2. Produce 2-4 charts. Each chart's labels array and each dataset's data array MUST have the same length.
3. Use realistic colors as hex codes for chart backgroundColor arrays. For doughnut/pie, provide one color per label. For bar/line, provide one color per label.
4. "type" must be exactly one of: "bar", "doughnut", "line", "horizontalBar". No other types.
5. Produce 3-6 insights. Each insight must reference specific numbers from the live data.
6. Produce 2-4 recommendations with priority.
7. All numbers must come from the LIVE DATA SNAPSHOT above. Do NOT invent data.
8. If the live data has zeros or empty sections, acknowledge it honestly (e.g. "No offers have been created yet").
9. The response must be valid JSON — no trailing commas, no comments, no markdown formatting.
10. For "horizontalBar" type charts, the frontend will render them as horizontal bar charts. The "labels" represent categories on the Y axis.
PROMPT;
    }

    /**
     * Load a context file from storage/app/ai-context/.
     */
    protected function loadContextFile(string $filename): string
    {
        $path = storage_path("app/ai-context/{$filename}");

        if (!file_exists($path)) {
            Log::warning("AI Report: context file missing: {$path}");
            return "(Context file {$filename} not found)";
        }

        return file_get_contents($path);
    }

    /**
     * Extract JSON from AI response, handling cases where the model
     * wraps the JSON in markdown code fences.
     */
    protected function extractJson(string $text): string
    {
        $text = trim($text);

        // Remove markdown code fences: ```json ... ``` or ``` ... ```
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $matches)) {
            return trim($matches[1]);
        }

        // If it starts with { and ends with }, return as-is
        if (str_starts_with($text, '{') && str_ends_with($text, '}')) {
            return $text;
        }

        // Last resort: find the first { and last }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return substr($text, $start, $end - $start + 1);
        }

        return $text;
    }
}
