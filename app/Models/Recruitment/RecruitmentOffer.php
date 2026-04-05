<?php

namespace App\Models\Recruitment;

use App\Contracts\Recruitment\Approvable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentOffer extends Model implements Approvable
{
    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_DECLINED  = 'declined';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'application_id',
        'offered_salary',
        'offer_letter_path',
        'offer_date',
        'offer_expiry_date',
        'status',
        'responded_at',
        'decline_reason',
        'issued_by',
        'approval_workflow_id',
        'notes',
    ];

    protected $casts = [
        'offer_date'        => 'date',
        'offer_expiry_date' => 'date',
        'responded_at'      => 'datetime',
        'offered_salary'    => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApprovalWorkflow::class, 'approval_workflow_id');
    }

    /**
     * Convenience accessor: get the candidate via the application relationship.
     */
    public function getCandidate(): ?RecruitmentCandidate
    {
        return $this->application?->candidate;
    }

    /**
     * Generate a signed URL for the offer letter (private disk).
     */
    public function getSignedLetterUrl(): ?string
    {
        if (! $this->offer_letter_path) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::signedRoute(
            'candidate.my-offers.download',
            ['offer' => $this->id]
        );
    }

    // ── Approvable Contract ───────────────────────────────────────────────

    public function approvalDocumentType(): string
    {
        return 'recruitment_offer';
    }

    public function submittedStatus(): string
    {
        return self::STATUS_SUBMITTED;
    }

    public function approvedStatus(): string
    {
        return self::STATUS_APPROVED;
    }

    public function rejectedStatus(): string
    {
        return self::STATUS_DRAFT;
    }

    public function draftStatus(): string
    {
        return self::STATUS_DRAFT;
    }

    public function onFullyApproved(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);

        // Update the linked application status to offer_pending
        $this->application?->update(['status' => 'offer_pending']);

        $candidate = $this->getCandidate();
        if (! $candidate) {
            return;
        }

        $portalOfferUrl = route('candidate.my-offers.show', $this->id);

        if (! $candidate->password) {
            $loginUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'candidate.offer-access',
                now()->addHours(72),
                ['offer' => $this->id]
            );
        } else {
            $loginUrl = route('candidate.login');
        }

        \Illuminate\Support\Facades\Mail::to($candidate->email)
            ->queue(new \App\Mail\Recruitment\RecruitmentOfferCandidateMail($this, $portalOfferUrl, $loginUrl));

        \Filament\Notifications\Notification::make()
            ->title('Offer Approved & Candidate Notified')
            ->body("The offer for {$candidate->first_name} {$candidate->last_name} has been approved. A congratulation email was sent to the candidate.")
            ->success()
            ->sendToDatabase($this->issuer);
    }

    public function onRejected(): void
    {
        $this->update(['status' => self::STATUS_DRAFT]);
    }

    /**
     * Dynamic view URL for the approval service notifications.
     */
    public function getApprovalViewUrl(): string
    {
        return \App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource::getUrl('view', ['record' => $this]);
    }

    /**
     * The mailable to send when submitted for approval.
     */
    public function getApprovalSubmittedMailable(string $viewUrl): object
    {
        return new \App\Mail\Recruitment\RecruitmentOfferSubmittedMail($this, $viewUrl);
    }
}
