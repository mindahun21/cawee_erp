<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration — Supplier Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f4f6f8;min-height:100vh;}
        .reg-header{background:#362A72;color:#fff;padding:0 1.5rem;height:60px;display:flex;align-items:center;justify-content:space-between;}
        .reg-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;color:#fff;}
        .reg-logo-mark{width:34px;height:34px;background:#6C5CE7;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;}
        .reg-logo-text{font-size:.9rem;font-weight:600;}
        .reg-header-links{display:flex;gap:1rem;}
        .reg-header-links a{color:rgba(255,255,255,.75);text-decoration:none;font-size:.875rem;}
        .reg-header-links a:hover{color:#fff;}
        .reg-strip{background:#6C5CE7;height:4px;}

        .reg-hero{background:linear-gradient(135deg,#362A72 60%,#2a2058);color:#fff;text-align:center;padding:3rem 1.5rem;}
        .reg-hero h1{font-size:2rem;font-weight:800;margin-bottom:.5rem;}
        .reg-hero p{color:rgba(255,255,255,.75);font-size:1rem;max-width:560px;margin:0 auto;}
        .reg-steps{display:flex;gap:2rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap;}
        .reg-step{display:flex;align-items:center;gap:.5rem;font-size:.85rem;opacity:.85;}
        .reg-step-num{width:24px;height:24px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0;border:1.5px solid rgba(255,255,255,.4);}

        .reg-body{max-width:900px;margin:2.5rem auto;padding:0 1.5rem 4rem;}

        .reg-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:2rem;margin-bottom:1.5rem;box-shadow:0 1px 6px rgba(0,0,0,.04);}
        .reg-card-title{font-size:1rem;font-weight:700;color:#362A72;padding-bottom:.875rem;border-bottom:2px solid #f0f4f8;margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem;}
        .reg-card-title .section-icon{width:28px;height:28px;background:#e0f4fc;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.9rem;}

        .form-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;}
        .form-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;}
        @media(max-width:640px){.form-grid-2,.form-grid-3{grid-template-columns:1fr;}}

        .form-group{margin-bottom:1rem;}
        .form-group.span2{grid-column:span 2;}
        .form-group.span3{grid-column:span 3;}
        @media(max-width:640px){.form-group.span2,.form-group.span3{grid-column:span 1;}}
        .form-group label{display:block;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#362A72;margin-bottom:.4rem;}
        .form-group label .req{color:#c0392b;margin-left:2px;}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:.6rem .875rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;font-family:inherit;transition:border-color .15s,box-shadow .15s;color:#1a2332;background:#fff;}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#6C5CE7;box-shadow:0 0 0 3px rgba(54,42,114,.15);}
        .form-group input.err,.form-group select.err,.form-group textarea.err{border-color:#c0392b!important;}
        .err-msg{font-size:.75rem;color:#c0392b;margin-top:.25rem;}
        .hint{font-size:.75rem;color:#6b7a90;margin-top:.25rem;}
        .form-group select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7a90' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .75rem center;padding-right:2.25rem;appearance:none;}
        .form-group textarea{resize:vertical;min-height:80px;}

        .alert{padding:.875rem 1.125rem;border-radius:8px;font-size:.875rem;margin-bottom:1.5rem;}
        .alert-error{background:#fde8e6;border:1px solid #f5b7b1;color:#8b1a1a;}

        .agreement{display:flex;align-items:flex-start;gap:.6rem;padding:1.25rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-top:.5rem;}
        .agreement input{width:16px;height:16px;margin-top:2px;accent-color:#6C5CE7;flex-shrink:0;}
        .agreement label{font-size:.85rem;color:#374151;cursor:pointer;}
        .agreement a{color:#6C5CE7;text-decoration:none;}

        .form-actions{display:flex;justify-content:space-between;align-items:center;margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e2e8f0;}
        .btn-submit{padding:.8rem 2.5rem;background:#362A72;color:#fff;border:none;border-radius:8px;font-family:inherit;font-size:1rem;font-weight:700;cursor:pointer;transition:background .15s;}
        .btn-submit:hover{background:#2a2058;}
        .btn-submit:disabled{opacity:.5;cursor:not-allowed;}
        .form-actions a{color:#6b7a90;text-decoration:none;font-size:.875rem;}
        .form-actions a:hover{color:#362A72;text-decoration:underline;}

        .pw-rules{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:.75rem;margin-top:.4rem;font-size:.75rem;color:#6b7a90;list-style:none;}
        .pw-rules li{padding:.1rem 0;}
        .pw-rules li::before{content:'·  ';}
    </style>
</head>
<body>

<header class="reg-header">
    <a href="{{ route('supplier.home') }}" class="reg-logo">
        <div class="reg-logo-mark">CE</div>
        <div class="reg-logo-text">Cawee ERP — Supplier Portal</div>
    </a>
    <div class="reg-header-links">
        <a href="{{ route('supplier.public.tenders') }}">Browse Tenders</a>
        <a href="{{ route('supplier.login') }}">Sign In</a>
    </div>
</header>
<div class="reg-strip"></div>

<div class="reg-hero">
    <h1>Vendor Registration</h1>
    <p>Join our supplier network to access procurement tenders and submit competitive bids.</p>
    <div class="reg-steps">
        <div class="reg-step"><div class="reg-step-num">1</div> Register company</div>
        <div class="reg-step"><div class="reg-step-num">2</div> Admin review &amp; approval</div>
        <div class="reg-step"><div class="reg-step-num">3</div> Access &amp; submit bids</div>
    </div>
</div>

<div class="reg-body">

    @if($errors->any())
    <div class="alert alert-error">
        <strong>Please fix the following errors:</strong><br>
        @foreach($errors->all() as $e)• {{ $e }}<br>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('supplier.register.submit') }}" enctype="multipart/form-data">
        @csrf

        {{-- ── 1. Company Identity ── --}}
        <div class="reg-card">
            <div class="reg-card-title"><span class="section-icon">🏢</span> Company Identity</div>
            <div class="form-grid-2">
                <div class="form-group span2">
                    <label>Legal Company Name <span class="req">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="{{ $errors->has('name')?'err':'' }}" placeholder="e.g., Acme Trading PLC" required>
                    @error('name')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Vendor Category <span class="req">*</span></label>
                    <select name="category" class="{{ $errors->has('category')?'err':'' }}" required>
                        <option value="">— Select category —</option>
                        @foreach(['Goods','Services','Works','Consultancy','General'] as $cat)
                            <option value="{{ $cat }}" {{ old('category')==$cat?'selected':'' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>TIN / Tax ID Number</label>
                    <input type="text" name="tin_number" value="{{ old('tin_number') }}" placeholder="123456789">
                </div>
                <div class="form-group">
                    <label>VAT Registration Number</label>
                    <input type="text" name="vat_number" value="{{ old('vat_number') }}" placeholder="VAT-XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Company Website</label>
                    <input type="url" name="website" value="{{ old('website') }}" placeholder="https://www.company.com">
                </div>
            </div>
        </div>

        {{-- ── 2. Contact Information ── --}}
        <div class="reg-card">
            <div class="reg-card-title"><span class="section-icon">👤</span> Contact Information</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Contact Person Full Name <span class="req">*</span></label>
                    <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="{{ $errors->has('contact_person')?'err':'' }}" placeholder="e.g., Abebe Bekele" required>
                    @error('contact_person')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Title / Position</label>
                    <input type="text" name="contact_person_title" value="{{ old('contact_person_title') }}" placeholder="e.g., Procurement Manager">
                </div>
                <div class="form-group">
                    <label>Official Email <span class="req">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="{{ $errors->has('email')?'err':'' }}" placeholder="contact@company.com" required>
                    @error('email')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Company Phone <span class="req">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="{{ $errors->has('phone')?'err':'' }}" placeholder="+251 91 123 4567" required>
                    @error('phone')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Contact Direct Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="+251 91 xxx xxxx">
                </div>
            </div>
        </div>

        {{-- ── 3. Address ── --}}
        <div class="reg-card">
            <div class="reg-card-title"><span class="section-icon">📍</span> Business Address</div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Country <span class="req">*</span></label>
                    <select name="country" class="{{ $errors->has('country')?'err':'' }}" required>
                        <option value="">— Select —</option>
                        @foreach(['Ethiopia','Kenya','Uganda','Tanzania','Djibouti','Somalia','Other'] as $c)
                            <option value="{{ $c }}" {{ old('country','Ethiopia')==$c?'selected':'' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>City <span class="req">*</span></label>
                    <input type="text" name="city" value="{{ old('city') }}" class="{{ $errors->has('city')?'err':'' }}" placeholder="Addis Ababa" required>
                    @error('city')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>State / Region</label>
                    <input type="text" name="state" value="{{ old('state') }}" placeholder="Oromia">
                </div>
                <div class="form-group">
                    <label>ZIP / Postal Code</label>
                    <input type="text" name="zip_code" value="{{ old('zip_code') }}" placeholder="1000">
                </div>
                <div class="form-group span2">
                    <label>Street Address <span class="req">*</span></label>
                    <input type="text" name="address" value="{{ old('address') }}" class="{{ $errors->has('address')?'err':'' }}" placeholder="Bole Road, Building No. 12" required>
                    @error('address')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ── 4. Bank & Payment ── --}}
        <div class="reg-card">
            <div class="reg-card-title"><span class="section-icon">🏦</span> Banking &amp; Payment Details</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Bank Name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="Commercial Bank of Ethiopia">
                </div>
                <div class="form-group">
                    <label>Account Number</label>
                    <input type="text" name="bank_account" value="{{ old('bank_account') }}" placeholder="1000012345678">
                </div>
                <div class="form-group">
                    <label>Branch Name</label>
                    <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" placeholder="Bole Branch">
                </div>
                <div class="form-group">
                    <label>Payment Terms</label>
                    <select name="payment_terms">
                        <option value="">— Select —</option>
                        @foreach(['Net 30','Net 60','Net 90','Advance Payment','50% Advance','Upon Delivery'] as $t)
                            <option value="{{ $t }}" {{ old('payment_terms')==$t?'selected':'' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Preferred Currency</label>
                    <select name="currency">
                        @foreach(['ETB','USD','EUR','GBP'] as $c)
                            <option value="{{ $c }}" {{ old('currency','ETB')==$c?'selected':'' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ── 5. Registration Documents (optional) ── --}}
        <div class="reg-card">
            <div class="reg-card-title"><span class="section-icon">📎</span> Supporting Documents</div>
            <div class="form-group span2">
                <label>Registration Documents</label>
                <input type="file" name="attachments[]" multiple>
                <div class="hint">You may upload registration certificates, licenses or other supporting documents (PDF, DOC, XLS, images, ZIP; max 10MB each).</div>
                @error('attachments')<div class="err-msg">{{ $message }}</div>@enderror
                @error('attachments.*')<div class="err-msg">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ── 6. Account Credentials ── --}}
        <div class="reg-card">
            <div class="reg-card-title"><span class="section-icon">🔐</span> Portal Account Credentials</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Password <span class="req">*</span></label>
                    <input type="password" name="password" class="{{ $errors->has('password')?'err':'' }}" placeholder="Min. 8 characters" required>
                    @error('password')<div class="err-msg">{{ $message }}</div>@enderror
                    <ul class="pw-rules">
                        <li>At least 8 characters</li>
                        <li>Must contain letters and numbers</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span class="req">*</span></label>
                    <input type="password" name="password_confirmation" placeholder="Repeat password" required>
                </div>
            </div>

            <div class="agreement">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    I confirm that all information provided is accurate and I agree to the
                    <a href="{{ route('supplier.public.tenders') }}">Procurement Portal Terms &amp; Conditions</a>.
                    I understand that my registration is subject to review and approval.
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('supplier.login') }}">← Already have an account? Sign in</a>
            <button type="submit" class="btn-submit">Submit Registration →</button>
        </div>

    </form>
</div>
</body>
</html>
