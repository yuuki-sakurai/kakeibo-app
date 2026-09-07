<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()?->only('id', 'name', 'email'),
            'csrfToken' => $request->session()->token(),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);
        $credentials = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => $this->passwordRules()]);
        $key = 'login:'.hash('sha256', $credentials['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'ログイン試行回数が多すぎます。しばらく待ってからお試しください。'], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($key));
        }
        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'メールアドレスまたはパスワードが違います。']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return $this->session($request);
    }

    public function register(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [...$this->passwordRules(), 'confirmed', Password::min(12)],
        ]);
        try {
            $user = User::create($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'このメールアドレスは登録済みです。']);
        }
        Auth::login($user);
        $request->session()->regenerate();

        return $this->session($request)->setStatusCode(201);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->session($request);
    }

    private function passwordRules(): array
    {
        return ['required', 'string', 'max:72', function ($attribute, $value, $fail) {
            if (strlen($value) > 72 || str_contains($value, "\0")) {
                $fail('パスワードを短くしてください。半角英数字の場合は72文字までです。');
            }
        }];
    }

    private function normalizeEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
    }
}
