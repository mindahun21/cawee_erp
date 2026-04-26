<?php

namespace App\Filament\Pages;

use App\Traits\BelongsToModulePage;

use App\Services\AI\RecruitmentIntelligenceService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class RecruitmentAiReport extends Page
{
    use BelongsToModulePage;

    protected string $view = 'filament.pages.recruitment-ai-report';

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $title = 'AI Report';
    protected static ?string $navigationLabel = 'AI Report';
    protected static ?int $navigationSort = 95;
    protected static string $routePath = 'recruitment-ai-report';

    public string $question = '';
    public string $selectedPreset = 'overview';
    public ?array $report = null;
    public bool $isLoading = false;

    protected static array $presets = [
        'overview' => 'Give me a comprehensive overview of our current recruitment pipeline. Analyze all plans, campaigns, applications, interviews, and offers. Highlight key metrics, bottlenecks, and conversion rates.',
        'campaigns' => 'Analyze our active recruitment campaigns. Which campaigns are performing well and which are struggling? Look at application counts vs vacancies, time remaining, and skills in demand.',
        'pipeline' => 'Analyze the candidate pipeline efficiency. What are the conversion rates at each stage (applied → reviewed → shortlisted → interviewed → selected → hired)? Where are the biggest drop-off points?',
        'hiring_speed' => 'Analyze our hiring speed and time-to-fill metrics. How long are campaigns staying active? Are there bottlenecks in the approval workflows for plans, campaigns, or offers?',
        'skills' => 'Analyze the skills landscape. What skills are most in demand across active campaigns? How does our candidate pool match the required skills? Are there skill gaps we need to address?',
    ];

    protected static array $presetLabels = [
        'overview'     => '📊 Pipeline Overview',
        'campaigns'    => '📢 Campaign Performance',
        'pipeline'     => '🔄 Conversion Analysis',
        'hiring_speed' => '⏱️ Hiring Speed',
        'skills'       => '🎯 Skills Gap Analysis',
    ];

    public function mount(): void
    {
        $this->question = static::$presets['overview'];
    }

    public function selectPreset(string $preset): void
    {
        if (isset(static::$presets[$preset])) {
            $this->selectedPreset = $preset;
            $this->question = static::$presets[$preset];
        }
    }

    public function generate(): void
    {
        if (blank($this->question)) {
            Notification::make()
                ->title('Please enter a question')
                ->warning()
                ->send();
            return;
        }

        $this->isLoading = true;
        $this->report = null;

        try {
            /** @var RecruitmentIntelligenceService $service */
            $service = app(RecruitmentIntelligenceService::class);
            $result = $service->analyze($this->question);

            if ($result['success']) {
                $this->report = $result['report'];
            } else {
                Notification::make()
                    ->title('Analysis Failed')
                    ->body($result['error'])
                    ->danger()
                    ->duration(8000)
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Unexpected Error')
                ->body($e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getViewData(): array
    {
        return [
            'presetLabels' => static::$presetLabels,
        ];
    }
}
