<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Recruitment\RecruitmentApplication;
use App\Models\Recruitment\RecruitmentApplicationStatusLog;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Models\Recruitment\RecruitmentChannel;
use App\Models\Recruitment\RecruitmentSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidateApplicationController extends Controller
{
    /** Columns that actually exist in recruitment_candidates — cached once per request. */
    private static ?array $candidateColumns = null;

    private static ?array $applicationColumns = null;

    private static function candidateColumns(): array
    {
        return self::$candidateColumns ??= Schema::getColumnListing('recruitment_candidates');
    }

    private static function applicationColumns(): array
    {
        return self::$applicationColumns ??= Schema::getColumnListing('recruitment_applications');
    }

    /** Show the apply form for a given campaign. */
    public function create(RecruitmentCampaign $campaign)
    {
        if (! $campaign->is_public) {
            abort(404);
        }

        if ($campaign->status === RecruitmentCampaign::STATUS_PAUSED) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'Applications for this position are temporarily paused.');
        }

        if ($campaign->status === RecruitmentCampaign::STATUS_FULL) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'Applications for this position have reached maximum capacity and are now full.');
        }

        if ($campaign->status === RecruitmentCampaign::STATUS_CLOSED) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'Applications for this position are now closed.');
        }

        if ($campaign->end_date && $campaign->end_date->isPast()) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'The application deadline for this position has passed.');
        }

        if ($campaign->status !== RecruitmentCampaign::STATUS_ACTIVE) {
            abort(404);
        }

        if (! Auth::guard('candidate')->check()) {
            return redirect()->route('candidate.login')
                ->with('info', 'Please sign in to apply for this position.');
        }

        $candidate = Auth::guard('candidate')->user();

        $alreadyApplied = RecruitmentApplication::where('candidate_id', $candidate->id)
            ->where('campaign_id', $campaign->id)
            ->exists();

        if ($alreadyApplied) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'You have already applied for this position.');
        }

        $campaign->load(['jobPosition', 'channel']);

        return view('recruitment.portal.apply', compact('campaign', 'candidate'));
    }

    /** Handle the application form submission. */
    public function store(Request $request, RecruitmentCampaign $campaign)
    {
        if (! $campaign->is_public) {
            abort(404);
        }

        if ($campaign->status === RecruitmentCampaign::STATUS_PAUSED) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'Applications for this position are temporarily paused.');
        }

        if ($campaign->status === RecruitmentCampaign::STATUS_FULL) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'Applications for this position have reached maximum capacity and are now full.');
        }

        if ($campaign->status === RecruitmentCampaign::STATUS_CLOSED) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'Applications for this position are now closed.');
        }

        if ($campaign->end_date && $campaign->end_date->isPast()) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'The application deadline for this position has passed.');
        }

        if ($campaign->status !== RecruitmentCampaign::STATUS_ACTIVE) {
            abort(404);
        }

        if (! Auth::guard('candidate')->check()) {
            return redirect()->route('candidate.login');
        }

        $candidate = Auth::guard('candidate')->user();

        $alreadyApplied = RecruitmentApplication::where('candidate_id', $candidate->id)
            ->where('campaign_id', $campaign->id)
            ->exists();

        if ($alreadyApplied) {
            return redirect()->route('candidate.campaigns.show', $campaign)
                ->with('info', 'You have already applied for this position.');
        }

        $campaign->load('channel');
        $channel = $campaign->channel;

        $schema = $channel ? ($channel->form_schema ?? []) : [];
        $fieldDefinitions = RecruitmentChannel::availableFields();

        $candidateData = [];
        $applicationData = [];
        $skillIds = [];   // IDs of known skills to sync in pivot
        $unknownSkills = [];   // raw labels not found in DB

        foreach ($schema as $fieldRow) {
            $data = $fieldRow['data'] ?? $fieldRow;
            $fieldKey = $data['field_key'] ?? null;

            if (! $fieldKey || in_array($fieldKey, ['header', 'paragraph'])) {
                continue;
            }

            $definition = $fieldDefinitions[$fieldKey] ?? null;
            if (! $definition) {
                continue;
            }

            $target = $definition['target'] ?? 'candidate';
            $dbColumn = $definition['db_column'] ?? null;

            // ── Skills — special handling ──────────────────────────────────────
            if ($target === 'candidate_skills') {
                $submitted = $request->input('skills', []);
                if (! is_array($submitted)) {
                    $submitted = [$submitted];
                }

                // Options saved in the schema carry value=skillId for DB skills
                $schemaOptions = $data['options'] ?? [];
                $knownValueMap = collect($schemaOptions)
                    ->filter(fn ($o) => is_numeric($o['value'] ?? null))
                    ->keyBy('value'); // value => option

                foreach ($submitted as $raw) {
                    if (is_numeric($raw) && $knownValueMap->has($raw)) {
                        $skillIds[] = (int) $raw;
                    } else {
                        // Custom text or unknown value — treat as new skill name
                        $unknownSkills[] = $raw;
                    }
                }

                continue;
            }

            // ── File upload ────────────────────────────────────────────────────
            if ($definition['type'] === 'file' && $request->hasFile($fieldKey)) {
                $path = $request->file($fieldKey)->store('candidates/resumes');

                if ($target === 'application' && $dbColumn && in_array($dbColumn, self::applicationColumns())) {
                    $applicationData[$dbColumn] = $path;
                } elseif ($dbColumn && in_array($dbColumn, self::candidateColumns())) {
                    $candidateData[$dbColumn] = $path;
                }

                continue;
            }

            // ── Regular field ──────────────────────────────────────────────────
            if ($request->has($fieldKey) && $dbColumn) {
                $value = $request->input($fieldKey);

                if ($target === 'application' && in_array($dbColumn, self::applicationColumns())) {
                    $applicationData[$dbColumn] = $value;
                } elseif ($target === 'candidate' && in_array($dbColumn, self::candidateColumns())) {
                    $candidateData[$dbColumn] = $value;
                }
            }
        }

        // Also collect cover_letter from the form if present
        $coverLetter = $request->input('cover_letter');

        DB::transaction(function () use (
            $candidate, $campaign, $channel, $candidateData, $applicationData,
            $coverLetter, $skillIds, $unknownSkills
        ) {

            if (! empty($candidateData)) {
                $candidate->fill($candidateData);
                $candidate->save();
            }

            if (! empty($skillIds) || ! empty($unknownSkills)) {
                // Create any new skills from the unknown list
                $newIds = [];
                foreach ($unknownSkills as $skillName) {
                    $skill = RecruitmentSkill::firstOrCreate(
                        ['name' => $skillName],
                        ['name' => $skillName]
                    );
                    $newIds[] = $skill->id;
                }

                // Merge all IDs and sync the pivot (no detaching existing unrelated skills)
                $allSkillIds = array_unique(array_merge($skillIds, $newIds));
                $pivotData = array_fill_keys($allSkillIds, ['proficiency' => null]);
                $candidate->skills()->syncWithoutDetaching($pivotData);

                // Also update skills_snapshot for quick access
                $snapshotLabels = RecruitmentSkill::whereIn('id', $allSkillIds)->pluck('name')->toArray();
                foreach ($unknownSkills as $s) {
                    if (! in_array($s, $snapshotLabels)) {
                        $snapshotLabels[] = $s;
                    }
                }
                $candidate->skills_snapshot = $snapshotLabels;
                $candidate->save();
            }

            $app = RecruitmentApplication::create(array_merge(
                [
                    'candidate_id' => $candidate->id,
                    'campaign_id' => $campaign->id,
                    'channel_id' => $channel?->id,
                    'cover_letter' => $coverLetter,
                    'status' => RecruitmentApplication::STATUS_APPLIED,
                    'applied_at' => now(),
                ],
                $applicationData
            ));

            RecruitmentApplicationStatusLog::create([
                'application_id' => $app->id,
                'from_status' => 'none',
                'to_status' => RecruitmentApplication::STATUS_APPLIED,
                'changed_by' => null, // System/Candidate
                'reason' => 'Initial application submission',
            ]);
        });

        if ($campaign->max_applications > 0) {
            $appCount = RecruitmentApplication::where('campaign_id', $campaign->id)->count();
            if ($appCount >= $campaign->max_applications) {
                $campaign->update(['status' => RecruitmentCampaign::STATUS_FULL]);
            }
        }

        $successMessage = $channel?->success_message
            ?? 'Your application has been submitted successfully. We will be in touch!';

        return redirect()->route('candidate.campaigns.show', $campaign)
            ->with('application_success', $successMessage);
    }
}
