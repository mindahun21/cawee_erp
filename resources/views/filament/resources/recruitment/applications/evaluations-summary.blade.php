@php
    $evaluations = \App\Models\Recruitment\RecruitmentCandidateEvaluation::with(['interviewer', 'schedule', 'scores.criteria'])
        ->where('candidate_id', $getRecord()->candidate_id)
        ->whereHas('schedule', function($q) use ($getRecord) {
            $q->where('campaign_id', $getRecord()->campaign_id);
        })
        ->orderBy('created_at', 'asc')
        ->get();
@endphp

<div class="space-y-6">
    @if($evaluations->isEmpty())
        <div class="text-sm text-gray-500 italic">No evaluations have been submitted for this candidate yet.</div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($evaluations as $eval)
                <div class="fi-in-section rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                    <div class="border-b border-gray-100 dark:border-white/10 pb-4 mb-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $eval->schedule->name }}</h3>
                            <span class="inline-flex items-center justify-center min-h-6 px-2 py-0.5 text-sm font-medium tracking-tight rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                {{ number_format($eval->overall_score, 2) }} / 5
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Evaluator: <strong>{{ $eval->interviewer->name }}</strong>
                        </p>
                    </div>

                    <div class="space-y-4">
                        @foreach($eval->scores as $score)
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $score->criteria->name }}</span>
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $score->score }} / 5</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1.5 dark:bg-gray-700">
                                    <div class="bg-primary-600 h-1.5 rounded-full dark:bg-primary-500" style="width: {{ ($score->score / 5) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($eval->comments)
                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/10">
                        <h4 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-2">Overall Feedback</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $eval->comments }}</p>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
