<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('web') && ! $request->user('web')->is_active) {
            Auth::guard('web')->logout();
            $request->session()->migrate(true);
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['phone' => 'حساب کاربری شما غیرفعال شده است.']);
        }

        return $next($request);
    }
}
