@extends('recruitment.layouts.portal')

@section('title', 'Set Your Password')

@section('content')
<div style="max-width: 440px; margin: 0 auto; padding: 40px 20px;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="font-size: 3rem; margin-bottom: .75rem;">🔒</div>
        <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--navy); margin-bottom: .5rem;">Set Your Password</h1>
        <p style="color: var(--muted); font-size: .9rem; line-height: 1.6;">
            To keep your account secure, please set a password before viewing your offer. You can use this password to log in at any time.
        </p>
    </div>

    @if(session('info'))
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: .9rem 1.1rem; border-radius: 10px; margin-bottom: 1.25rem; font-size: .9rem;">
        {{ session('info') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: .9rem 1.1rem; border-radius: 10px; margin-bottom: 1.25rem; font-size: .9rem;">
        <ul style="margin: 0; padding-left: 1.25rem;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('candidate.set-password.save') }}" method="POST"
          style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 2rem;">
        @csrf

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-weight: 600; color: var(--navy); margin-bottom: .4rem; font-size: .9rem;">New Password</label>
            <input type="password" name="password" required minlength="8"
                   placeholder="At least 8 characters"
                   style="width: 100%; padding: .75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-weight: 600; color: var(--navy); margin-bottom: .4rem; font-size: .9rem;">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                   placeholder="Repeat your password"
                   style="width: 100%; padding: .75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; box-sizing: border-box;">
        </div>

        <button type="submit"
                style="width: 100%; background: #003366; color: #fff; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s;"
                onmouseover="this.style.background='#002244'"
                onmouseout="this.style.background='#003366'">
            Set Password & View My Offer →
        </button>
    </form>
</div>
@endsection
