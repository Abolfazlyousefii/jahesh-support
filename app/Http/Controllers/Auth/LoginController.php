<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $key = 'login:'.$request->validated('phone').'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'phone' => "تلاش‌های ورود بیش از حد مجاز است. {$seconds} ثانیه دیگر دوباره تلاش کنید.",
            ]);
        }

        $authenticated = Auth::attempt([
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $authenticated) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['phone' => 'شماره موبایل یا رمز عبور صحیح نیست.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }
}
