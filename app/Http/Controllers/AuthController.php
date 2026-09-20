<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password tidak sesuai.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'Akun Anda sedang dinonaktifkan. Hubungi administrator.',
                ])
                ->withInput($request->only('email'));
        }

        $role = $user->role;

        if (!in_array($role, ['admin', 'kelurahan', 'kecamatan'], true)) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'Akun tidak memiliki akses ke sistem.',
                ])
                ->withInput($request->only('email'));
        }

        $home = match ($role) {
            'admin' => route('admin.users.index'),
            'kecamatan' => route('dashboard'),
            'kelurahan' => route('kelurahan.index'),
        };

        return redirect($home);

    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }
}
