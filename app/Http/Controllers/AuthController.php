<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\MathCaptchaService;

class AuthController extends Controller
{
    public function showLogin(Request $request, MathCaptchaService $captcha)
    {
        $captchaQuestion = $captcha->issue($request);

        return view('auth.login', compact('captchaQuestion'));
    }

    public function login(Request $request, MathCaptchaService $captcha)
    {
        $captchaAnswer = $request->input('captcha_answer');

        if (!$captcha->verify($request, is_string($captchaAnswer) ? $captchaAnswer : null)) {
            return back()
                ->withErrors(['captcha_answer' => 'Jawaban captcha tidak sesuai atau sudah kedaluwarsa.'])
                ->withInput($request->only('email'));
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // ==================================================
        // ROLE YANG DIIZINKAN
        // ==================================================

        $allowedRoles = [
            'admin',
            'kecamatan',
            'kelurahan',
            'fo',
            'kasi_pemerintahan',
            'lurah',
            'kasi_umum',
            'sekcam',
            'camat',
        ];

        if (!Auth::attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            return back()
                ->withErrors([
                    'email' => 'Email atau password tidak sesuai.',
                ])
                ->withInput(
                    $request->only('email')
                );
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $role = $user->role;

        // ==================================================
        // VALIDASI AKSES AKUN
        // ==================================================

        $accessError = null;

        if (!$user->is_active) {
            $accessError = 'Akun Anda sedang dinonaktifkan. Hubungi administrator.';
        } elseif (!in_array($role, $allowedRoles, true)) {
            $accessError = 'Akun tidak memiliki akses ke sistem.';
        }

        if ($accessError !== null) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => $accessError,
                ])
                ->withInput(
                    $request->only('email')
                );
        }

        // ==================================================
        // HOME BERDASARKAN ROLE
        // ==================================================

        $home = match ($role) {
            'admin'     => route('admin.users.index'),
            'kecamatan' => route('dashboard'),
            'kelurahan' => route('kelurahan.index'),
            'fo' => route('kelurahan.index'),
            'kasi_pemerintahan', 'lurah', 'kasi_umum', 'sekcam', 'camat' => route('workflow.index'),
            default     => route('login'),
        };

        return redirect($home);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah keluar dari sistem.');
    }
}
