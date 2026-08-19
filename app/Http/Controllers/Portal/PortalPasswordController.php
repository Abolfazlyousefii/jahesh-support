<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateCustomerPasswordRequest;
use App\Models\CustomerPasswordResetCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PortalPasswordController extends Controller
{
    public function update(UpdateCustomerPasswordRequest $request): RedirectResponse
    {
        $customer = $request->user('customer');

        if (filled($customer->password)
            && ! Hash::check((string) $request->validated('current_password'), $customer->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'رمز عبور فعلی صحیح نیست.',
            ]);
        }

        $customer->forceFill([
            'password' => Hash::make($request->validated('password')),
            'password_changed_at' => now(),
        ])->save();

        CustomerPasswordResetCode::query()
            ->where('customer_id', $customer->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $request->session()->regenerate();

        return back()->with('status', 'رمز عبور حساب شما با موفقیت تغییر کرد.');
    }
}
