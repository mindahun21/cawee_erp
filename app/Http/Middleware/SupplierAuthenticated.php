<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('supplier')->check()) {
            return redirect()->route('supplier.login')
                ->with('intended', $request->url());
        }

        $supplier = auth('supplier')->user();

        if ($supplier->status === 'Blacklisted') {
            auth('supplier')->logout();
            return redirect()->route('supplier.login')
                ->withErrors(['email' => 'Your account has been suspended. Please contact procurement@company.com.']);
        }

        return $next($request);
    }
}
