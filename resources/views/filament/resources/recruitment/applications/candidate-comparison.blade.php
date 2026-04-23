<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6">
    <div class="fi-section-header px-6 py-4 flex flex-col gap-1 border-b border-gray-200 dark:border-white/10">
        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
            Requirement vs Reality Analysis
        </h3>
        <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
            A side-by-side matching algorithm comparing the Campaign's expected requirements against the actual Candidate's profile.
        </p>
    </div>
    
    <div class="fi-section-content p-6">
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full text-left text-sm whitespace-nowrap min-w-full">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 font-medium text-gray-950 dark:text-white">Criteria</th>
                        <th class="px-4 py-3 font-medium text-gray-950 dark:text-white">Campaign Requirement</th>
                        <th class="px-4 py-3 font-medium text-gray-950 dark:text-white">Candidate Reality</th>
                        <th class="px-4 py-3 font-medium text-gray-950 dark:text-white text-center">Match Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @php
                        $app = $getRecord();
                        $campaign = $app->campaign;
                        $candidate = $app->candidate;
                        
                        $comparisons = [
                            [
                                'label' => 'Expected Salary',
                                'req' => $campaign->salary_min ? (number_format($campaign->salary_min) . ($campaign->salary_max ? ' - ' . number_format($campaign->salary_max) : '+') . ' ' . $campaign->currency) : 'Any / Open',
                                'reality' => $app->desired_salary ? (number_format($app->desired_salary) . ' ' . $campaign->currency) : ($candidate->desired_salary ? number_format($candidate->desired_salary) . ' ' . $candidate->currency : 'Unspecified'),
                                'match' => function() use ($campaign, $app, $candidate) {
                                    $asked = $app->desired_salary ?? $candidate->desired_salary;
                                    if (!$asked && !$campaign->salary_max) return true;
                                    if (!$asked || !$campaign->salary_max) return null;
                                    return $asked <= $campaign->salary_max;
                                }
                            ],
                            [
                                'label' => 'Seniority Level',
                                'req' => $campaign->candidate_seniority ?? 'Any',
                                'reality' => $candidate->seniority ?? 'Unspecified',
                                'match' => function() use ($campaign, $candidate) {
                                    if (!$campaign->candidate_seniority || strtolower($campaign->candidate_seniority) === 'any') return true;
                                    if (!$candidate->seniority) return null;
                                    return strtolower($campaign->candidate_seniority) === strtolower($candidate->seniority);
                                }
                            ],
                            [
                                'label' => 'Gender',
                                'req' => $campaign->candidate_gender ?? 'Any',
                                'reality' => $candidate->gender ?? 'Unspecified',
                                'match' => function() use ($campaign, $candidate) {
                                    if (!$campaign->candidate_gender || strtolower($campaign->candidate_gender) === 'any') return true;
                                    if (!$candidate->gender) return null;
                                    return strtolower($campaign->candidate_gender) === strtolower($candidate->gender);
                                }
                            ],
                            [
                                'label' => 'Height',
                                'req' => $campaign->candidate_height_min ? 'Min ' . $campaign->candidate_height_min . ' m' : 'Any',
                                'reality' => $candidate->height_m ? $candidate->height_m . ' m' : 'Unspecified',
                                'match' => function() use ($campaign, $candidate) {
                                    if (!$campaign->candidate_height_min) return true;
                                    if (!$candidate->height_m) return null;
                                    return $candidate->height_m >= $campaign->candidate_height_min;
                                }
                            ],
                            [
                                'label' => 'Weight',
                                'req' => $campaign->candidate_weight_min ? 'Min ' . $campaign->candidate_weight_min . ' kg' : 'Any',
                                'reality' => $candidate->weight_kg ? $candidate->weight_kg . ' kg' : 'Unspecified',
                                'match' => function() use ($campaign, $candidate) {
                                    if (!$campaign->candidate_weight_min) return true;
                                    if (!$candidate->weight_kg) return null;
                                    return $candidate->weight_kg >= $campaign->candidate_weight_min;
                                }
                            ],
                            [
                                'label' => 'Key Skills',
                                'req' => $campaign->skills && $campaign->skills->count() > 0 ? $campaign->skills->pluck('name')->join(', ') : 'None specified',
                                'reality' => $candidate->skills_snapshot ? implode(', ', (array) $candidate->skills_snapshot) : 'Unspecified',
                                'match' => function() use ($campaign, $candidate) {
                                    if (!$campaign->skills || $campaign->skills->count() === 0) return true;
                                    if (!$candidate->skills_snapshot) return null;
                                    
                                    $reqSkills = $campaign->skills->pluck('name')->map(fn($s) => strtolower($s))->toArray();
                                    $candSkills = array_map('strtolower', (array) $candidate->skills_snapshot);
                                    
                                    return count(array_intersect($reqSkills, $candSkills)) > 0;
                                }
                            ],
                        ];
                        
                        $literacy_req = collect([$campaign->candidate_literacy])->filter()->first() ?? 'Any';
                        $literacy_cand = $candidate->literacies && $candidate->literacies->count() > 0 ? $candidate->literacies->pluck('degree')->join(', ') : 'Unspecified';
                        $comparisons[] = [
                            'label' => 'Education / Literacy',
                            'req' => $literacy_req,
                            'reality' => $literacy_cand,
                            'match' => function() use ($literacy_req, $literacy_cand) {
                                if ($literacy_req === 'Any') return true;
                                if ($literacy_cand === 'Unspecified') return null;
                                return stripos($literacy_cand, $literacy_req) !== false;
                            }
                        ];
                    @endphp
                    
                    @foreach($comparisons as $comp)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50/50 dark:bg-white/5">{{ $comp['label'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $comp['req'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $comp['reality'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $status = is_callable($comp['match']) ? $comp['match']() : null;
                                @endphp
                                @if($status === true)
                                    <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                        <svg class="mr-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        Match
                                    </span>
                                @elseif($status === false)
                                    <span class="inline-flex items-center rounded-md bg-danger-50 px-2 py-1 text-xs font-medium text-danger-700 ring-1 ring-inset ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                        <svg class="mr-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                        Mismatch
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                        Pending Insight
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
