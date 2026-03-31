@extends('recruitment.layouts.portal')

@section('title', 'Link Expired')

@section('content')
<div style="max-width: 480px; margin: 4rem auto; text-align: center; padding: 3rem 2rem; background: #fff; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,.05);">
    <div style="font-size: 4rem; margin-bottom: 1.5rem;">🔗</div>
    <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--navy); margin-bottom: 1rem;">Link Expired or Invalid</h1>
    <p style="color: var(--muted); font-size: 1rem; line-height: 1.6; margin-bottom: 2rem;">
        The secure link you used to access this offer has expired or is no longer valid. These links are time-limited for your security.
    </p>
    
    <div style="background: #f8fafc; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; text-align: left; border: 1px solid var(--border);">
        <h4 style="font-size: .9rem; font-weight: 700; color: var(--navy); margin-bottom: .5rem;">What can I do?</h4>
        <ul style="margin: 0; padding-left: 1.25rem; font-size: .88rem; color: var(--text); line-height: 1.6;">
            <li>If you have already set a password, please <a href="{{ route('candidate.login') }}" style="color: var(--teal); font-weight: 600; text-decoration: none;">Sign In</a> directly.</li>
            <li>If you haven't set a password, check for more recent emails or contact our HR team to request a new link.</li>
        </ul>
    </div>

    <a href="{{ route('candidate.login') }}" 
       style="display: inline-block; background: var(--navy); color: #fff; padding: .85rem 2.5rem; border-radius: 10px; font-weight: 700; text-decoration: none; transition: background .2s;"
       onmouseover="this.style.background='#002244'"
       onmouseout="this.style.background='#003366'">
        Go to Login Page
    </a>
</div>
@endsection
