<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CandidateAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('candidate')->check()) {
            return redirect()->route('candidate.login')
                ->with('intended', $request->url());
        }

        return $next($request);
    }
}
