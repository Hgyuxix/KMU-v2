<?php

namespace App\Services;

use Illuminate\Http\Request;

class MathCaptchaService
{
    private const SESSION_KEY = 'math_captcha';

    public function issue(Request $request): string
    {
        $left = random_int(2, 9);
        $right = random_int(2, 9);
        $operator = random_int(0, 1) === 0 ? '+' : '-';

        if ($operator === '-' && $right > $left) {
            [$left, $right] = [$right, $left];
        }

        $answer = $operator === '+'
            ? $left + $right
            : $left - $right;

        $request->session()->put(self::SESSION_KEY, [
            'answer' => (string) $answer,
            'issued_at' => now()->timestamp,
        ]);

        return "{$left} {$operator} {$right}";
    }

    public function verify(Request $request, ?string $submitted): bool
    {
        $challenge = $request->session()->pull(self::SESSION_KEY);

        if (
            !is_array($challenge) ||
            !isset($challenge['answer'], $challenge['issued_at']) ||
            $challenge['issued_at'] < now()->subMinutes(10)->timestamp ||
            $submitted === null
        ) {
            return false;
        }

        return hash_equals(
            (string) $challenge['answer'],
            trim($submitted)
        );
    }
}
