<?php

namespace Tests\Feature\Scenarios;

use App\Contracts\PasswordResetOtpSender;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;

#[Group('release')]
class CustomerSecurityWorkflowScenarioTest extends ScenarioTestCase
{
    public function test_customer_can_complete_password_login_change_and_recovery_lifecycle(): void
    {
        $sender = new ScenarioPasswordResetOtpSender;
        $this->app->instance(PasswordResetOtpSender::class, $sender);

        $customer = $this->customerWithPhone('09350000011', 'StartSecure123');
        $customer->phones()->create([
            'phone' => '09900000011',
            'is_primary' => false,
        ]);

        // ورود با یکی از شماره‌های فرعی مشتری.
        $this->post('/portal/login/password', [
            'phone' => '۰۹۹۰۰۰۰۰۰۱۱',
            'password' => 'StartSecure123',
        ])->assertRedirect('/portal');

        $this->assertAuthenticatedAs($customer, 'customer');

        // نشست مشتری نباید دسترسی Staff ایجاد کند.
        $this->get('/dashboard')->assertRedirect('/login');

        // تغییر رمز از حساب من.
        $this->put('/portal/profile/password', [
            'current_password' => 'StartSecure123',
            'password' => 'ChangedSecure123',
            'password_confirmation' => 'ChangedSecure123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('ChangedSecure123', $customer->fresh()->password));
        $this->assertNotNull($customer->fresh()->password_changed_at);

        $this->post('/portal/logout')->assertRedirect('/portal/login');
        $this->assertGuest('customer');

        // رمز قبلی دیگر نباید معتبر باشد.
        $this->post('/portal/login/password', [
            'phone' => '09350000011',
            'password' => 'StartSecure123',
        ])->assertSessionHasErrors('password');

        // رمز جدید باید قابل استفاده باشد.
        $this->post('/portal/login/password', [
            'phone' => '09350000011',
            'password' => 'ChangedSecure123',
        ])->assertRedirect('/portal');

        $this->assertAuthenticatedAs($customer, 'customer');
        $this->post('/portal/logout')->assertRedirect('/portal/login');

        // بازیابی رمز با OTP.
        $this->post('/portal/forgot-password', [
            'phone' => '09350000011',
        ])->assertRedirect('/portal/forgot-password/verify');

        $this->assertSame('09350000011', $sender->phone);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $sender->code);

        $this->post('/portal/forgot-password/verify', [
            'code' => $sender->code,
        ])->assertRedirect('/portal/forgot-password/reset');

        $this->post('/portal/forgot-password/reset', [
            'password' => 'RecoveredSecure123',
            'password_confirmation' => 'RecoveredSecure123',
        ])->assertRedirect('/portal/login');

        $this->assertTrue(Hash::check('RecoveredSecure123', $customer->fresh()->password));

        $this->post('/portal/login/password', [
            'phone' => '09900000011',
            'password' => 'RecoveredSecure123',
        ])->assertRedirect('/portal');

        $this->assertAuthenticatedAs($customer, 'customer');
    }
}

final class ScenarioPasswordResetOtpSender implements PasswordResetOtpSender
{
    public ?string $phone = null;

    public ?string $code = null;

    public function send(string $phone, string $code): void
    {
        $this->phone = $phone;
        $this->code = $code;
    }
}
