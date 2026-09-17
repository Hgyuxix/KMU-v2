<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/login');

        // User yang sudah login diarahkan ke home role-nya masing-masing
        // supaya tidak perlu nabrak 403 dulu baru di-redirect.
        $middleware->redirectUsersTo(function ($request) {
            $role = $request->user()?->role;

            return match ($role) {
                'kecamatan' => route('dashboard'),
                'kelurahan'  => route('layanan.index'),
                default      => route('login'),
            };
        });

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
