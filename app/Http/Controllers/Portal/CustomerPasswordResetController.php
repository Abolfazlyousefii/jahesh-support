<?php

namespace App\Http\Controllers\Portal;

use App\Actions\CustomerAuth\RequestCustomerPasswordResetAction;
use App\Actions\CustomerAuth\ResetCustomerPasswordAction;
use App\Actions\CustomerAuth\VerifyCustomerPasswordResetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\RequestCustomerPasswordResetRequest;
use App\Http\Requests\Portal\ResetCustomerPasswordRequest;
use App\Http\Requests\Portal\VerifyCustomerPasswordResetCodeRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CustomerPasswordResetController extends Controller
{
    public function create(Request $request): View
    {
        $this->clearResetAuthorization($request);

        return view('portal.auth.forgot-password');
    }

    public function requestCode(
        RequestCustomerPasswordResetRequest $request,
        RequestCustomerPasswordResetAction $action,
    ): RedirectResponse {
        $phone = $request->validated('phone');

        try {
            $action->execute($phone, $request->ip());
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->safe()->only('phone'))
                ->withErrors(['phone' => 'ارسال کد بازیابی موقتاً امکان‌پذیر نیست. لطفاً دوباره تلاش کنید.']);
        }

        $this->clearResetAuthorization($request);
        $request->session()->put('customer_password_reset_phone', $phone);

        return redirect()
            ->route('portal.password.verify')
            ->with('status', 'اگر این شماره در سامانه ثبت شده باشد، کد بازیابی برای آن ارسال می‌شود.');
    }

    public function verification(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('customer_password_reset_phone')) {
            return redirect()->route('portal.password.forgot');
        }

        return view('portal.auth.forgot-password-verify', [
            'cooldown' => (int) config('jahesh.password_reset.cooldown_seconds', 60),
        ]);
    }

    public function verify(
        VerifyCustomerPasswordResetCodeRequest $request,
        VerifyCustomerPasswordResetAction $action,
    ): RedirectResponse {
        $phone = (string) $request->session()->get('customer_password_reset_phone');

        if ($phone === '') {
            return redirect()->route('portal.password.forgot');
        }

        $customer = $action->execute($phone, $request->validated('code'));

        if ($customer === null) {
            throw ValidationException::withMessages([
                'code' => 'کد بازیابی صحیح نیست یا اعتبار آن پایان یافته است.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'customer_password_reset_customer_id' => $customer->id,
            'customer_password_reset_verified_at' => now()->timestamp,
        ]);

        return redirect()->route('portal.password.reset');
    }

    public function resend(Request $request, RequestCustomerPasswordResetAction $action): RedirectResponse
    {
        $phone = (string) $request->session()->get('customer_password_reset_phone');

        if ($phone === '') {
            return redirect()->route('portal.password.forgot');
        }

        try {
            $action->execute($phone, $request->ip());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['code' => 'ارسال مجدد کد بازیابی موقتاً امکان‌پذیر نیست.']);
        }

        return back()->with('status', 'در صورت امکان، کد بازیابی دوباره ارسال شد.');
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        if (! $this->hasValidResetAuthorization($request)) {
            $this->clearResetAuthorization($request);

            return redirect()
                ->route('portal.password.forgot')
                ->withErrors(['phone' => 'اعتبار بازیابی رمز عبور پایان یافته است. لطفاً دوباره کد دریافت کنید.']);
        }

        return view('portal.auth.reset-password');
    }

    public function reset(
        ResetCustomerPasswordRequest $request,
        ResetCustomerPasswordAction $action,
    ): RedirectResponse {
        if (! $this->hasValidResetAuthorization($request)) {
            $this->clearResetAuthorization($request);

            return redirect()
                ->route('portal.password.forgot')
                ->withErrors(['phone' => 'اعتبار بازیابی رمز عبور پایان یافته است. لطفاً دوباره کد دریافت کنید.']);
        }

        $customerId = (int) $request->session()->get('customer_password_reset_customer_id');
        $phone = (string) $request->session()->get('customer_password_reset_phone');

        $customer = Customer::query()->active()->find($customerId);

        if ($customer === null || ! $customer->phones()->where('phone', $phone)->exists()) {
            $this->clearResetAuthorization($request);

            return redirect()->route('portal.password.forgot');
        }

        $action->execute($customer, $phone, $request->validated('password'));
        $this->clearResetAuthorization($request);
        $request->session()->regenerate();

        return redirect()
            ->route('portal.login')
            ->with('status', 'رمز عبور شما با موفقیت تغییر کرد. اکنون می‌توانید با رمز جدید وارد شوید.');
    }

    private function hasValidResetAuthorization(Request $request): bool
    {
        $verifiedAt = (int) $request->session()->get('customer_password_reset_verified_at', 0);
        $customerId = (int) $request->session()->get('customer_password_reset_customer_id', 0);
        $phone = (string) $request->session()->get('customer_password_reset_phone', '');

        if ($verifiedAt <= 0 || $customerId <= 0 || $phone === '') {
            return false;
        }

        $validFor = (int) config('jahesh.password_reset.verified_minutes', 10);

        return $verifiedAt >= now()->subMinutes($validFor)->timestamp;
    }

    private function clearResetAuthorization(Request $request): void
    {
        $request->session()->forget([
            'customer_password_reset_phone',
            'customer_password_reset_customer_id',
            'customer_password_reset_verified_at',
        ]);
    }
}
