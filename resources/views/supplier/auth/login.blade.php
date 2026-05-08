<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Supplier Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#362A72;min-height:100vh;display:flex;align-items:center;justify-content:center;}
        .auth-wrap{width:100%;max-width:460px;padding:1.5rem;}
        .auth-logo{text-align:center;margin-bottom:2rem;}
        .auth-logo-mark{width:56px;height:56px;background:#362A72;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:1.4rem;color:#fff;margin-bottom:.75rem;}
        .auth-logo h1{color:#fff;font-size:1.5rem;font-weight:700;}
        .auth-logo p{color:rgba(255,255,255,.6);font-size:.85rem;margin-top:.25rem;}
        .auth-card{background:#fff;border-radius:16px;padding:2.5rem;box-shadow:0 24px 64px rgba(0,0,0,.3);}
        .auth-card h2{font-size:1.25rem;font-weight:700;color:#362A72;margin-bottom:.35rem;}
        .auth-card .subtitle{font-size:.875rem;color:#6b7a90;margin-bottom:1.75rem;}
        .form-group{margin-bottom:1.25rem;}
        .form-group label{display:block;font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#362A72;margin-bottom:.4rem;}
        .form-group input{width:100%;padding:.65rem .875rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;font-family:inherit;transition:border-color .15s,box-shadow .15s;}
        .form-group input:focus{outline:none;border-color:#6C5CE7;box-shadow:0 0 0 3px rgba(54,42,114,.15);}
        .form-group input.err{border-color:#c0392b;}
        .err-msg{font-size:.78rem;color:#c0392b;margin-top:.3rem;}
        .forgot{font-size:.8rem;color:#6C5CE7;text-decoration:none;float:right;margin-top:-.8rem;margin-bottom:1rem;display:block;text-align:right;}
        .forgot:hover{text-decoration:underline;}
        .btn-primary{width:100%;padding:.75rem;background:#362A72;color:#fff;border:none;border-radius:8px;font-family:inherit;font-size:1rem;font-weight:700;cursor:pointer;transition:background .15s;margin-top:.5rem;}
        .btn-primary:hover{background:#5a4bd1;}
        .auth-footer{text-align:center;margin-top:1.5rem;font-size:.875rem;color:#6b7a90;}
        .auth-footer a{color:#6C5CE7;text-decoration:none;font-weight:600;}
        .auth-footer a:hover{text-decoration:underline;}
        .remember-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;}
        .remember-row label{display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#1a2332;cursor:pointer;}
        .remember-row input{accent-color:#6C5CE7;width:15px;height:15px;}
        .alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1.25rem;}
        .alert-success{background:#dcf5ea;border:1px solid #a7d9bc;color:#0d5c38;}
        .alert-error{background:#fde8e6;border:1px solid #f5b7b1;color:#8b1a1a;}
        .divider{display:flex;align-items:center;gap:.75rem;margin:1.25rem 0;color:#9ca3af;font-size:.8rem;}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0;}
        .pending-note{background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;padding:.875rem;font-size:.8rem;color:#1e40af;margin-bottom:1rem;}
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-logo">
        <div class="auth-logo-mark">CE</div>
        <h1>Supplier Portal</h1>
        <p>Cawee ERP — Procurement System</p>
    </div>

    <div class="auth-card">
        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to access tenders and submit bids.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $e){{ $e }}<br>@endforeach
            </div>
        @endif

        <div class="pending-note">
            🔑 New vendors must complete registration and await approval before portal access is granted.
        </div>

        <form method="POST" action="{{ route('supplier.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="company@example.com" class="{{ $errors->has('email') ? 'err' : '' }}" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" class="{{ $errors->has('password') ? 'err' : '' }}" required>
            </div>
            <div class="remember-row">
                <label>
                    <input type="checkbox" name="remember"> Remember me
                </label>
            </div>
            <button type="submit" class="btn-primary">Sign In to Portal</button>
        </form>

        <div class="auth-footer">
            Not yet registered? <a href="{{ route('supplier.register') }}">Apply as a Vendor</a>
        </div>
        <div class="auth-footer" style="margin-top:.5rem;">
            <a href="{{ route('supplier.public.tenders') }}">← Browse public tenders</a>
        </div>
    </div>
</div>
</body>
</html>
