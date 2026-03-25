<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/supplier.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/candidate.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('recruitment') || $request->is('recruitment/*')) {
                return route('candidate.login');
            }
            if ($request->is('portal') || $request->is('portal/*') || $request->is('supplier/*')) {
                return route('supplier.login');
            }
            return route('filament.admin.auth.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
