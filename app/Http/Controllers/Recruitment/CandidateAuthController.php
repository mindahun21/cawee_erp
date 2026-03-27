<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Recruitment\RecruitmentCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CandidateAuthController extends Controller
{
    public function showLogin()
    {
        if (auth('candidate')->check()) {
            return redirect()->route('candidate.campaigns');
        }
        return view('recruitment.portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $candidate = RecruitmentCandidate::where('email', $credentials['email'])->first();

        if (! $candidate || ! Hash::check($credentials['password'], $candidate->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        Auth::guard('candidate')->login($candidate, $request->boolean('remember'));

        return redirect()->intended(route('candidate.campaigns'));
    }

    public function showRegister()
    {
        if (auth('candidate')->check()) {
            return redirect()->route('candidate.campaigns');
        }
        return view('recruitment.portal.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:200'],
            'last_name'  => ['nullable', 'string', 'max:200'],
            'email'      => ['required', 'email', 'max:150', 'unique:recruitment_candidates,email'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'password'   => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['portal_access'] = true;

        try {
            $candidate = RecruitmentCandidate::create($data);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()->withErrors(['email' => 'An account with this email already exists, or there was a system error generating your candidate ID. Please try again.'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'An unexpected error occurred during registration. Please try again later.'])->withInput();
        }

        Auth::guard('candidate')->login($candidate);

        return redirect()->route('candidate.campaigns');
    }

    public function logout(Request $request)
    {
        Auth::guard('candidate')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('candidate.login');
    }
}
