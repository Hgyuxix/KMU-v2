<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            // Kalau user belum login, biarkan middleware auth yang handle.
            // Kalau sudah login tapi role salah, arahkan ke home-nya sendiri
            // daripada tampilkan halaman error 403 yang membingungkan.
            if (!$user) {
                return redirect()->route('login');
            }

            $home = match ($user->role) {
                'kecamatan' => route('dashboard'),
                'kelurahan'  => route('layanan.index'),
                default      => route('login'),
            };

            return redirect($home)->with(
                'info',
                'Anda tidak memiliki akses ke halaman tersebut.'
            );
        }

        return $next($request);
    }
}
