<?php

namespace App\Providers;

use App\Models\Permohonan;
use App\Observers\PermohonanObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Permohonan::observe(PermohonanObserver::class);

        RateLimiter::for('verification', fn (Request $request) =>
            Limit::perMinute(30)->by($request->ip())
        );
    }
}
