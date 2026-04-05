<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Recruitment\RecruitmentOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class CandidateOfferController extends Controller
{
    /**
     * LIST — My Offers page
     */
    public function index()
    {
        $candidate = Auth::guard('candidate')->user();

        $offers = RecruitmentOffer::whereHas('application', fn ($q) => $q->where('candidate_id', $candidate->id))
            ->with([
                'application.campaign.jobPosition',
                'application.candidate',
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('recruitment.portal.my-offers', compact('offers'));
    }

    /**
     * SHOW — Single offer detail page
     */
    public function show(RecruitmentOffer $offer)
    {
        $candidate = Auth::guard('candidate')->user();

        if ($offer->application?->candidate_id !== $candidate->id) {
            abort(403);
        }

        $offer->load('application.campaign.jobPosition', 'application.candidate', 'issuer');

        return view('recruitment.portal.show-offer', compact('offer'));
    }

    /**
     * ACCEPT — Candidate accepts the offer
     */
    public function accept(Request $request, RecruitmentOffer $offer)
    {
        $candidate = Auth::guard('candidate')->user();

        if ($offer->application?->candidate_id !== $candidate->id) {
            abort(403);
        }

        if (!in_array($offer->status, [RecruitmentOffer::STATUS_APPROVED])) {
            return back()->with('error', 'This offer cannot be accepted at this time.');
        }

        $offer->update([
            'status'       => RecruitmentOffer::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $offer->application?->update(['status' => 'offer_accepted']);

        if ($offer->application) {
            \App\Events\Recruitment\CandidateHired::dispatch($offer->application);
        }

        if ($issuer = $offer->issuer) {
            $candidateName = $candidate->full_name;
            $jobTitle = $offer->application?->campaign?->jobPosition?->title ?? 'Position';
            $viewUrl = \App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource::getUrl('view', ['record' => $offer]);

            \Filament\Notifications\Notification::make()
                ->title('Employment Offer Accepted')
                ->body("{$candidateName} has accepted the offer for \"{$jobTitle}\".")
                ->success()
                ->icon('heroicon-o-check-circle')
                ->actions([
                    \Filament\Actions\Action::make('view')
                        ->label('View Offer')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($issuer);
        }

        return redirect()->route('candidate.my-offers.show', $offer)
            ->with('success', 'You have accepted the offer! The HR team will contact you with further onboarding details. Congratulations! 🎉');
    }

    /**
     * DECLINE — Candidate declines the offer
     */
    public function decline(Request $request, RecruitmentOffer $offer)
    {
        $candidate = Auth::guard('candidate')->user();

        if ($offer->application?->candidate_id !== $candidate->id) {
            abort(403);
        }

        if (!in_array($offer->status, [RecruitmentOffer::STATUS_APPROVED])) {
            return back()->with('error', 'This offer cannot be declined at this time.');
        }

        $request->validate([
            'decline_reason' => 'nullable|string|max:1000',
        ]);

        $offer->update([
            'status'         => RecruitmentOffer::STATUS_DECLINED,
            'responded_at'   => now(),
            'decline_reason' => $request->decline_reason,
        ]);

        $offer->application?->update(['status' => 'offer_declined']);

        if ($issuer = $offer->issuer) {
            $candidateName = $candidate->full_name;
            $jobTitle = $offer->application?->campaign?->jobPosition?->title ?? 'Position';
            $viewUrl = \App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource::getUrl('view', ['record' => $offer]);

            \Filament\Notifications\Notification::make()
                ->title('Employment Offer Declined')
                ->body("{$candidateName} has declined the offer for \"{$jobTitle}\".")
                ->danger()
                ->icon('heroicon-o-x-circle')
                ->actions([
                    \Filament\Actions\Action::make('view')
                        ->label('View Offer')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($issuer);
        }

        return redirect()->route('candidate.my-offers')
            ->with('success', 'You have declined the offer. If you have questions, feel free to contact our HR team.');
    }

    /**
     * DOWNLOAD — Download the offer letter PDF
     */
    public function downloadLetter(\Illuminate\Http\Request $request, RecruitmentOffer $offer)
    {
        $candidate = \Illuminate\Support\Facades\Auth::guard('candidate')->user();

        if ($offer->application?->candidate_id !== $candidate->id) {
            abort(403);
        }

        if (! $offer->offer_letter_path || ! \Illuminate\Support\Facades\Storage::disk('private')->exists($offer->offer_letter_path)) {
            abort(404, 'Offer letter not found.');
        }

        return \Illuminate\Support\Facades\Storage::disk('private')->download(
            $offer->offer_letter_path,
            'Employment_Offer_Letter.pdf'
        );
    }

    /**
     * SECURE LINK ACCESS — for first-time candidates with no password.
     * URL is time-limited and signed.
     */
    public function accessViaLink(Request $request, RecruitmentOffer $offer)
    {
        // Laravel validates the signature, but we double-check here too
        if (! $request->hasValidSignature()) {
            return view('recruitment.portal.auth.link-expired');
        }

        $candidate = $offer->application?->candidate;

        if (! $candidate) {
            abort(404);
        }

        // Auto log in the candidate
        Auth::guard('candidate')->login($candidate);

        // If candidate has no password yet, force them to set one first
        if (! $candidate->password) {
            // Store intended destination in session
            session(['offer_redirect_after_password' => $offer->id]);
            return redirect()->route('candidate.set-password')
                ->with('info', 'Please set a password to secure your account before viewing your offer.');
        }

        return redirect()->route('candidate.my-offers.show', $offer);
    }

    /**
     * SHOW SET PASSWORD FORM — for first-time portal users
     */
    public function showSetPasswordForm()
    {
        if (! Auth::guard('candidate')->check()) {
            return redirect()->route('candidate.login');
        }

        return view('recruitment.portal.auth.set-password');
    }

    /**
     * SAVE SET PASSWORD
     */
    public function savePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $candidate = Auth::guard('candidate')->user();

        $candidate->update([
            'password' => Hash::make($request->password),
        ]);

        // Redirect to the offer if they came via signed link
        $offerId = session()->pull('offer_redirect_after_password');
        if ($offerId) {
            $offer = RecruitmentOffer::find($offerId);
            if ($offer) {
                return redirect()->route('candidate.my-offers.show', $offer)
                    ->with('success', 'Password set successfully! Here is your offer.');
            }
        }

        return redirect()->route('candidate.my-offers')
            ->with('success', 'Password set! You can now log in anytime using your email and this password.');
    }
}
