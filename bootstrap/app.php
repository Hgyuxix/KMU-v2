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

        // User yang sudah login diarahkan ke home role-nya masing-masing.
        $middleware->redirectUsersTo(function ($request) {
            $role = $request->user()?->role;

            return match ($role) {
                'admin'     => route('admin.users.index'),
                'kecamatan' => route('dashboard'),
                'kelurahan' => route('kelurahan.index'),
                default     => route('login'),
            };
        });

        $middleware->alias([
            'role'   => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
