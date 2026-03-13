<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SupplierAuthController extends Controller
{
    // ── Login ──────────────────────────────────────────────────────
    public function showLogin()
    {
        if (auth('supplier')->check()) {
            return redirect()->route('supplier.dashboard');
        }
        return view('supplier.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $supplier = Supplier::where('email', $credentials['email'])->first();

        if (! $supplier || ! Hash::check($credentials['password'], $supplier->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        if (! $supplier->portal_access) {
            return back()->withErrors(['email' => 'Your portal access is pending admin approval. You will be notified by email.'])->withInput();
        }

        if ($supplier->status === 'Blacklisted') {
            return back()->withErrors(['email' => 'Your account has been suspended.'])->withInput();
        }

        Auth::guard('supplier')->login($supplier, $request->boolean('remember'));

        return redirect()->intended(route('supplier.dashboard'));
    }

    // ── Register ───────────────────────────────────────────────────
    public function showRegister()
    {
        if (auth('supplier')->check()) {
            return redirect()->route('supplier.dashboard');
        }
        return view('supplier.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            // Company
            'name'                  => ['required', 'string', 'max:200'],
            'category'              => ['required', 'in:Goods,Services,Works,Consultancy,General'],
            'tin_number'            => ['nullable', 'string', 'max:50'],
            'vat_number'            => ['nullable', 'string', 'max:50'],
            'website'               => ['nullable', 'url', 'max:200'],
            // Contact
            'email'                 => ['required', 'email', 'max:150', 'unique:procurement_suppliers,email'],
            'phone'                 => ['required', 'string', 'max:50'],
            'contact_person'        => ['required', 'string', 'max:150'],
            'contact_person_title'  => ['nullable', 'string', 'max:50'],
            'contact_phone'         => ['nullable', 'string', 'max:50'],
            // Address
            'country'               => ['required', 'string', 'max:100'],
            'city'                  => ['required', 'string', 'max:100'],
            'state'                 => ['nullable', 'string', 'max:100'],
            'zip_code'              => ['nullable', 'string', 'max:20'],
            'address'               => ['required', 'string', 'max:300'],
            // Bank
            'bank_name'             => ['nullable', 'string', 'max:100'],
            'bank_account'          => ['nullable', 'string', 'max:100'],
            'bank_branch'           => ['nullable', 'string', 'max:150'],
            'payment_terms'         => ['nullable', 'string', 'max:100'],
            'currency'              => ['nullable', 'string', 'max:10'],
            // Documents
            'attachments'           => ['nullable', 'array'],
            'attachments.*'         => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,zip,png,jpg,jpeg'],
            // Auth
            'password'              => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // Store uploaded registration documents (if any)
        $paths = [];
        foreach ($request->file('attachments', []) as $file) {
            $paths[] = $file->store('procurement/suppliers/portal', 'local');
        }
        if (! empty($paths)) {
            $data['attachments'] = $paths;
        }

        $data['password']       = Hash::make($data['password']);
        $data['portal_access']  = false; // admin must approve
        $data['status']         = 'Inactive'; // active after admin review

        $supplier = Supplier::create($data);

        return redirect()->route('supplier.login')
            ->with('success', 'Registration submitted! Our procurement team will review your application and send you an email once approved.');
    }

    // ── Logout ─────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::guard('supplier')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('supplier.login');
    }
}
