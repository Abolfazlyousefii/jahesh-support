<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer !== null && (! $customer->is_active || $customer->trashed())) {
            Auth::guard('customer')->logout();
            $request->session()->migrate(true);
            $request->session()->regenerateToken();

            return redirect()->route('portal.login')->withErrors(['phone' => 'حساب مشتری غیرفعال است.']);
        }

        return $next($request);
    }
}
