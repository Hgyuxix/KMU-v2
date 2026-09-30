<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(Request $request, ?string $token): bool
    {
        $secretKey = config('services.turnstile.secret_key');

        if (!is_string($token) || trim($token) === '' || !is_string($secretKey) || $secretKey === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (ConnectionException) {
            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
