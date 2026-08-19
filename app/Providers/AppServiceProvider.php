<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Contracts\PasswordResetOtpSender;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\TaskPolicy;
use App\Policies\TicketPolicy;
use App\Services\Otp\LogOtpSender;
use App\Services\Sms\SmartOtpSender;
use App\Services\Sms\SmartPasswordResetOtpSender;
use App\Support\DatePresenter;
use App\Support\PhoneNormalizer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatePresenter::class);

        $this->app->bind(OtpSender::class, function ($app) {
            $driver = (string) config('jahesh.otp.driver', 'auto');

            return match ($driver) {
                'log' => $app->make(LogOtpSender::class),
                'auto', 'melipayamak' => $app->make(SmartOtpSender::class),
                default => throw new \RuntimeException('OTP driver is not configured.'),
            };
        });

        $this->app->bind(PasswordResetOtpSender::class, function ($app) {
            $driver = (string) config('jahesh.otp.driver', 'auto');

            return match ($driver) {
                'log' => $app->make(SmartPasswordResetOtpSender::class),
                'auto', 'melipayamak' => $app->make(SmartPasswordResetOtpSender::class),
                default => throw new \RuntimeException('Password reset OTP driver is not configured.'),
            };
        });
    }

    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);

        RateLimiter::for('customer-otp-request', fn (Request $request) => Limit::perMinutes(5, 3)->by(
            PhoneNormalizer::normalize($request->string('phone')->toString()).'|'.$request->ip(),
        ));
        RateLimiter::for('customer-otp-verify', fn (Request $request) => Limit::perMinute(10)->by(
            ((string) $request->session()->get('customer_login_phone')).'|'.$request->ip(),
        ));

        RateLimiter::for('customer-password-reset-request', fn (Request $request) => Limit::perMinutes(5, 3)->by(
            PhoneNormalizer::normalize($request->string('phone')->toString()
                ?: (string) $request->session()->get('customer_password_reset_phone')).'|'.$request->ip(),
        ));
        RateLimiter::for('customer-password-reset-verify', fn (Request $request) => Limit::perMinute(10)->by(
            ((string) $request->session()->get('customer_password_reset_phone')).'|'.$request->ip(),
        ));

        Gate::before(function ($user): ?bool {
            return $user instanceof User && $user->hasRole('super-admin') ? true : null;
        });
    }
}
