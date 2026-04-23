@extends('recruitment.layouts.portal')

@section('title', 'Candidate Registration')

@section('content')
<div style="max-width: 500px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 12px; border: 1px solid var(--border);">
    <h1 style="font-size: 1.5rem; font-weight: 700; text-align: center; margin-bottom: 1.5rem;">Create an Account</h1>

    @if($errors->any())
        <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem;">
            <ul style="margin:0; padding-left:1.5rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('candidate.register.submit') }}">
        @csrf
        <div style="margin-bottom: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label style="display: block; font-size: .9rem; font-weight: 600; margin-bottom: .4rem;">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required style="width: 100%; padding: .65rem; border: 1px solid var(--border); border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: .9rem; font-weight: 600; margin-bottom: .4rem;">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" style="width: 100%; padding: .65rem; border: 1px solid var(--border); border-radius: 6px;">
            </div>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-size: .9rem; font-weight: 600; margin-bottom: .4rem;">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required style="width: 100%; padding: .65rem; border: 1px solid var(--border); border-radius: 6px;">
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-size: .9rem; font-weight: 600; margin-bottom: .4rem;">Phone Optional</label>
            <input type="text" name="phone" value="{{ old('phone') }}" style="width: 100%; padding: .65rem; border: 1px solid var(--border); border-radius: 6px;">
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-size: .9rem; font-weight: 600; margin-bottom: .4rem;">Password</label>
            <input type="password" name="password" required style="width: 100%; padding: .65rem; border: 1px solid var(--border); border-radius: 6px;">
        </div>
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-size: .9rem; font-weight: 600; margin-bottom: .4rem;">Confirm Password</label>
            <input type="password" name="password_confirmation" required style="width: 100%; padding: .65rem; border: 1px solid var(--border); border-radius: 6px;">
        </div>
        <button type="submit" style="width: 100%; padding: .75rem; background: var(--teal); color: #fff; font-weight: 600; border: none; border-radius: 6px; cursor: pointer;">
            Register
        </button>
    </form>

    <div style="text-align: center; margin-top: 1rem; font-size: .9rem;">
        Already have an account? <a href="{{ route('candidate.login') }}" style="color: var(--teal);">Login here</a>
    </div>
</div>
@endsection
