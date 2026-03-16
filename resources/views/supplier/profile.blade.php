@extends('supplier.layouts.portal')
@section('title', 'Company Profile')
@section('content')
<div class="sp-page">

    <div class="sp-page-header">
        <h1>Company Profile</h1>
        <p>Manage your vendor information, addresses, banking details and login credentials.</p>
    </div>

    @if(session('success'))
    <div class="sp-alert sp-alert-success">{{ session('success') }}</div>
    @endif

    {{-- ── Profile form ── --}}
    <form method="POST" action="{{ route('supplier.profile.update') }}">
        @csrf @method('PATCH')

        {{-- Company (read-only, managed by admin) --}}
        <div class="sp-card">
            <div class="sp-card-header">
                <div><div class="sp-card-title">Company Identity</div><div class="sp-card-sub">Managed by procurement admin</div></div>
                @if($supplier->status === 'Active') <span class="badge badge-success">Active</span>
                @else <span class="badge badge-warn">{{ $supplier->status }}</span> @endif
            </div>
            <div class="sp-grid-3">
                @foreach([
                    ['Company Name', $supplier->name],
                    ['Vendor Code', $supplier->code ?? $supplier->vendor_code ?? '—'],
                    ['Category', $supplier->category],
                    ['TIN Number', $supplier->tin_number ?? '—'],
                    ['VAT Number', $supplier->vat_number ?? '—'],
                ] as [$l,$v])
                <div>
                    <div style="font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.3rem;">{{ $l }}</div>
                    <div style="font-size:.9rem;font-weight:500;">{{ $v }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Contact --}}
        <div class="sp-card">
            <div class="sp-card-title" style="margin-bottom:1.5rem;">Contact Information</div>
            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <label>Company Phone <span style="color:var(--danger)">*</span></label>
                    <input name="phone" type="text" class="sp-input" value="{{ old('phone',$supplier->phone) }}" required>
                    @error('phone')<div class="sp-error-msg">{{ $message }}</div>@enderror
                </div>
                <div class="sp-form-group">
                    <label>Company Website</label>
                    <input name="website" type="url" class="sp-input" value="{{ old('website',$supplier->website) }}" placeholder="https://...">
                </div>
                <div class="sp-form-group">
                    <label>Contact Person <span style="color:var(--danger)">*</span></label>
                    <input name="contact_person" type="text" class="sp-input" value="{{ old('contact_person',$supplier->contact_person) }}" required>
                </div>
                <div class="sp-form-group">
                    <label>Title / Position</label>
                    <input name="contact_person_title" type="text" class="sp-input" value="{{ old('contact_person_title',$supplier->contact_person_title) }}">
                </div>
                <div class="sp-form-group">
                    <label>Contact Direct Phone</label>
                    <input name="contact_phone" type="text" class="sp-input" value="{{ old('contact_phone',$supplier->contact_phone) }}">
                </div>
                <div class="sp-form-group">
                    <label>VAT Number</label>
                    <input name="vat_number" type="text" class="sp-input" value="{{ old('vat_number',$supplier->vat_number) }}">
                </div>
                <div class="sp-form-group">
                    <label>TIN Number</label>
                    <input name="tin_number" type="text" class="sp-input" value="{{ old('tin_number',$supplier->tin_number) }}">
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="sp-card">
            <div class="sp-card-title" style="margin-bottom:1.5rem;">Business Address</div>
            <div class="sp-grid-3">
                <div class="sp-form-group">
                    <label>Country <span style="color:var(--danger)">*</span></label>
                    <select name="country" class="sp-select" required>
                        @foreach(['Ethiopia','Kenya','Uganda','Tanzania','Djibouti','Somalia','Other'] as $c)
                            <option value="{{ $c }}" {{ old('country',$supplier->country)==$c?'selected':'' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sp-form-group">
                    <label>City <span style="color:var(--danger)">*</span></label>
                    <input name="city" type="text" class="sp-input" value="{{ old('city',$supplier->city) }}" required>
                </div>
                <div class="sp-form-group">
                    <label>State / Region</label>
                    <input name="state" type="text" class="sp-input" value="{{ old('state',$supplier->state) }}">
                </div>
                <div class="sp-form-group">
                    <label>ZIP / Postal Code</label>
                    <input name="zip_code" type="text" class="sp-input" value="{{ old('zip_code',$supplier->zip_code) }}">
                </div>
                <div class="sp-form-group" style="grid-column:span 2;">
                    <label>Street Address <span style="color:var(--danger)">*</span></label>
                    <input name="address" type="text" class="sp-input" value="{{ old('address',$supplier->address) }}" required>
                </div>
            </div>
            <hr class="sp-divider">
            <div class="sp-section-label">Billing / Shipping</div>
            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <label>Billing Address</label>
                    <textarea name="billing_address" class="sp-textarea" rows="3">{{ old('billing_address',$supplier->billing_address) }}</textarea>
                </div>
                <div class="sp-form-group">
                    <label>Shipping Address</label>
                    <textarea name="shipping_address" class="sp-textarea" rows="3" {{ old('same_as_billing',$supplier->same_as_billing)?'disabled':'' }}>{{ old('shipping_address',$supplier->shipping_address) }}</textarea>
                    <div class="sp-hint" style="margin-top:.4rem;">
                        <label style="display:flex;align-items:center;gap:.4rem;font-weight:400;text-transform:none;letter-spacing:0;font-size:.83rem;cursor:pointer;">
                            <input type="checkbox" name="same_as_billing" value="1" {{ old('same_as_billing',$supplier->same_as_billing)?'checked':'' }}> Same as billing address
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Banking --}}
        <div class="sp-card">
            <div class="sp-card-title" style="margin-bottom:1.5rem;">Banking &amp; Payment</div>
            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <label>Bank Name</label>
                    <input name="bank_name" type="text" class="sp-input" value="{{ old('bank_name',$supplier->bank_name) }}">
                </div>
                <div class="sp-form-group">
                    <label>Account Number</label>
                    <input name="bank_account" type="text" class="sp-input" value="{{ old('bank_account',$supplier->bank_account) }}">
                </div>
                <div class="sp-form-group">
                    <label>Branch</label>
                    <input name="bank_branch" type="text" class="sp-input" value="{{ old('bank_branch',$supplier->bank_branch) }}">
                </div>
                <div class="sp-form-group">
                    <label>SWIFT / BIC</label>
                    <input name="bank_swift" type="text" class="sp-input" value="{{ old('bank_swift',$supplier->bank_swift) }}" placeholder="CBETETAA">
                </div>
                <div class="sp-form-group">
                    <label>IBAN</label>
                    <input name="bank_iban" type="text" class="sp-input" value="{{ old('bank_iban',$supplier->bank_iban) }}">
                </div>
                <div class="sp-form-group">
                    <label>Payment Terms</label>
                    <select name="payment_terms" class="sp-select">
                        <option value="">— Select —</option>
                        @foreach(['Net 30','Net 60','Net 90','Advance Payment','50% Advance','Upon Delivery'] as $t)
                            <option value="{{ $t }}" {{ old('payment_terms',$supplier->payment_terms)==$t?'selected':'' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sp-form-group">
                    <label>Preferred Currency</label>
                    <select name="currency" class="sp-select">
                        @foreach(['ETB','USD','EUR','GBP'] as $c)
                            <option value="{{ $c }}" {{ old('currency',$supplier->currency)==$c?'selected':'' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="sp-form-group" style="margin-top:.5rem;">
                <label>Return Policy</label>
                <textarea name="return_policy" class="sp-textarea" rows="3">{{ old('return_policy',$supplier->return_policy) }}</textarea>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1rem;">
            <button type="submit" class="sp-btn sp-btn-navy sp-btn-lg">Save Profile Changes</button>
        </div>
    </form>

    {{-- ── Password change ── --}}
    <div class="sp-card" style="margin-top:2rem;">
        <div class="sp-card-title" style="margin-bottom:1.5rem;">Change Password</div>
        @error('current_password')<div class="sp-alert sp-alert-error">{{ $message }}</div>@enderror
        <form method="POST" action="{{ route('supplier.profile.password') }}">
            @csrf @method('PATCH')
            <div class="sp-grid-3">
                <div class="sp-form-group">
                    <label>Current Password <span style="color:var(--danger)">*</span></label>
                    <input name="current_password" type="password" class="sp-input {{ $errors->has('current_password')?'error':'' }}" required>
                </div>
                <div class="sp-form-group">
                    <label>New Password <span style="color:var(--danger)">*</span></label>
                    <input name="password" type="password" class="sp-input {{ $errors->has('password')?'error':'' }}" required>
                </div>
                <div class="sp-form-group">
                    <label>Confirm New Password</label>
                    <input name="password_confirmation" type="password" class="sp-input" required>
                </div>
            </div>
            <button type="submit" class="sp-btn sp-btn-primary">Update Password</button>
        </form>
    </div>

</div>
@endsection
