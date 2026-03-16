<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Mail\BidAwardedMail;
use App\Models\Procurement\Bid;
use App\Models\Procurement\BidCriterionScore;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class SupplierPortalController extends Controller
{
    private function supplier(): Supplier
    {
        return auth('supplier')->user();
    }

    // ── Dashboard ──────────────────────────────────────────────────
    public function dashboard()
    {
        $supplier = $this->supplier();
        $myBids   = Bid::where('supplier_id', $supplier->id)
                       ->with('tender')
                       ->latest()
                       ->take(5)
                       ->get();

        $openTenders = Tender::where('status', 'Published')
                             ->where(function ($q) use ($supplier) {
                                 $q->where('visibility', 'public')
                                   ->orWhereHas('invitedSuppliers', fn ($sub) => $sub->where('supplier_id', $supplier->id));
                             })
                             ->where('submission_deadline', '>=', now())
                             ->latest('submission_deadline')
                             ->take(4)
                             ->get();

        return view('supplier.dashboard', compact('supplier', 'myBids', 'openTenders'));
    }

    // ── Tender Index ───────────────────────────────────────────────
    public function tenders(Request $request)
    {
        $supplier = $this->supplier();
        $query = Tender::where('status', 'Published')
                       ->where(function ($q) use ($supplier) {
                           $q->where('visibility', 'public')
                             ->orWhereHas('invitedSuppliers', fn ($sub) => $sub->where('supplier_id', $supplier->id));
                       })
                       ->with('evaluationCriteria');

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%$search%")
                                       ->orWhere('tender_number', 'like', "%$search%")
                                       ->orWhere('method', 'like', "%$search%"));
        }

        if ($method = $request->get('method')) {
            $query->where('method', $method);
        }

        $tenders = $query->latest('submission_deadline')->paginate(12);

        return view('supplier.tenders.index', compact('tenders'));
    }

    // ── Tender Detail ──────────────────────────────────────────────
    public function tenderShow(Tender $tender)
    {
        $supplier = $this->supplier();
        $isVisible = $tender->visibility === 'public'
            || $tender->invitedSuppliers()->where('supplier_id', $supplier->id)->exists();

        abort_unless($tender->status === 'Published' && $isVisible, 404);

        $tender->load('evaluationCriteria', 'requisition');
        $myBid = Bid::where('supplier_id', $this->supplier()->id)
                    ->where('tender_id', $tender->id)
                    ->first();

        return view('supplier.tenders.show', compact('tender', 'myBid'));
    }

    // ── Submit Bid ─────────────────────────────────────────────────
    public function bidCreate(Tender $tender)
    {
        $supplier = $this->supplier();
        $isVisible = $tender->visibility === 'public'
            || $tender->invitedSuppliers()->where('supplier_id', $supplier->id)->exists();

        abort_unless($tender->status === 'Published' && $isVisible, 403, 'This tender is not open for bids.');
        abort_unless($tender->submission_deadline >= now()->toDateString(), 403, 'Submission deadline has passed.');

        $existing = Bid::where('supplier_id', $this->supplier()->id)
                       ->where('tender_id', $tender->id)
                       ->exists();
        if ($existing) {
            return redirect()->route('supplier.tenders.show', $tender)->with('info', 'You have already submitted a bid for this tender.');
        }

        $tender->load('evaluationCriteria');
        return view('supplier.bids.create', compact('tender'));
    }

    public function bidStore(Request $request, Tender $tender)
    {
        $supplier = $this->supplier();
        $isVisible = $tender->visibility === 'public'
            || $tender->invitedSuppliers()->where('supplier_id', $supplier->id)->exists();

        abort_unless($tender->status === 'Published' && $isVisible, 403);
        abort_unless($tender->submission_deadline >= now()->toDateString(), 403, 'Submission deadline has passed.');

        $data = $request->validate([
            'reference_number'              => ['nullable', 'string', 'max:100'],
            'bid_amount'                    => ['required', 'numeric', 'min:0'],
            'currency'                      => ['required', 'string', 'max:10'],
            'delivery_days'                 => ['required', 'integer', 'min:1'],
            'validity_date'                 => ['required', 'date', 'after:today'],
            'bid_security'                  => ['nullable', 'string', 'max:150'],
            'conflict_of_interest_declared' => ['boolean'],
            'notes'                         => ['nullable', 'string'],
            'attachments'                   => ['required', 'array', 'min:1'],
            'attachments.*'                 => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,zip,png,jpg'],
            'criterion_responses'           => ['nullable', 'array'],
            'criterion_responses.*'         => ['required_with:criterion_responses', 'string', 'max:5000'],
        ]);

        // Store attachments
        $paths = [];
        foreach ($request->file('attachments', []) as $file) {
            $paths[] = $file->store('procurement/bids/portal', 'local');
        }
        $data['attachments']   = $paths;
        $data['tender_id']     = $tender->id;
        $data['supplier_id']   = $this->supplier()->id;
        $data['submission_date'] = now()->toDateString();
        $data['status']        = 'Submitted';
        $data['conflict_of_interest_declared'] = $request->boolean('conflict_of_interest_declared');

        // Remove from main data — stored separately
        $criterionResponses = $data['criterion_responses'] ?? [];
        unset($data['criterion_responses']);

        $bid = Bid::create($data);

        // Pre-seed BidCriterionScore rows so evaluators see the supplier's
        // written response alongside their numeric score
        foreach ($criterionResponses as $criterionId => $responseText) {
            BidCriterionScore::updateOrCreate(
                ['bid_id' => $bid->id, 'criterion_id' => $criterionId],
                ['score' => null, 'notes' => $responseText]
            );
        }

        return redirect()->route('supplier.my-bids')
            ->with('success', "Your bid for {$tender->tender_number} has been submitted successfully. You will be notified of the outcome.");
    }

    // ── My Bids ────────────────────────────────────────────────────
    public function myBids()
    {
        $bids = Bid::where('supplier_id', $this->supplier()->id)
                   ->with('tender')
                   ->latest('submission_date')
                   ->paginate(15);

        return view('supplier.bids.my-bids', compact('bids'));
    }

    // ── Profile ────────────────────────────────────────────────────
    public function profile()
    {
        return view('supplier.profile', ['supplier' => $this->supplier()]);
    }

    public function profileUpdate(Request $request)
    {
        $supplier = $this->supplier();
        $data = $request->validate([
            'phone'                 => ['required', 'string', 'max:50'],
            'website'               => ['nullable', 'url', 'max:200'],
            'contact_person'        => ['required', 'string', 'max:150'],
            'contact_person_title'  => ['nullable', 'string', 'max:50'],
            'contact_phone'         => ['nullable', 'string', 'max:50'],
            'country'               => ['required', 'string', 'max:100'],
            'city'                  => ['required', 'string', 'max:100'],
            'state'                 => ['nullable', 'string', 'max:100'],
            'zip_code'              => ['nullable', 'string', 'max:20'],
            'address'               => ['required', 'string', 'max:300'],
            'billing_address'       => ['nullable', 'string'],
            'shipping_address'      => ['nullable', 'string'],
            'same_as_billing'       => ['boolean'],
            'bank_name'             => ['nullable', 'string', 'max:100'],
            'bank_account'          => ['nullable', 'string', 'max:100'],
            'bank_branch'           => ['nullable', 'string', 'max:150'],
            'bank_swift'            => ['nullable', 'string', 'max:50'],
            'bank_iban'             => ['nullable', 'string', 'max:100'],
            'payment_terms'         => ['nullable', 'string', 'max:100'],
            'currency'              => ['nullable', 'string', 'max:10'],
            'return_policy'         => ['nullable', 'string'],
            'vat_number'            => ['nullable', 'string', 'max:50'],
            'tin_number'            => ['nullable', 'string', 'max:50'],
        ]);

        $supplier->update($data);
        return back()->with('success', 'Profile updated successfully.');
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $supplier = $this->supplier();
        if (! Hash::check($request->current_password, $supplier->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $supplier->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password changed successfully.');
    }

    // ── Public tender listing (no login required) ──────────────────
    public function publicTenders(Request $request)
    {
        $query = Tender::where('status', 'Published')
            ->where('visibility', 'public');

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%$search%")
                                       ->orWhere('tender_number', 'like', "%$search%"));
        }

        $tenders = $query->latest('submission_deadline')->paginate(12);

        return view('supplier.public.tenders', compact('tenders'));
    }

    public function publicTenderShow(Tender $tender)
    {
        abort_unless($tender->status === 'Published' && $tender->visibility === 'public', 404);
        $tender->load('evaluationCriteria');
        return view('supplier.public.tender-detail', compact('tender'));
    }
}
