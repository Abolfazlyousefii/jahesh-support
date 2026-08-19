<?php

namespace Tests\Feature;

use App\Contracts\PasswordResetOtpSender;
use App\Models\Customer;
use App\Models\CustomerPasswordResetCode;
use App\Models\CustomerPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPortalPasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    private CapturingPasswordResetOtpSender $sender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = new CapturingPasswordResetOtpSender;
        $this->app->instance(PasswordResetOtpSender::class, $this->sender);
    }

    public function test_active_customer_can_request_password_reset_with_any_registered_phone(): void
    {
        $customer = $this->customerWithPhone('09121111111');
        $customer->phones()->create(['phone' => '09351111111', 'is_primary' => false]);

        $this->post('/portal/forgot-password', ['phone' => '۰۹۳۵۱۱۱۱۱۱۱'])
            ->assertRedirect('/portal/forgot-password/verify');

        $this->assertSame('09351111111', $this->sender->phone);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->sender->code);
        $this->assertDatabaseHas('customer_password_reset_codes', [
            'customer_id' => $customer->id,
            'phone' => '09351111111',
        ]);
        $this->assertDatabaseMissing('customer_password_reset_codes', [
            'code_hash' => $this->sender->code,
        ]);
    }

    public function test_unknown_inactive_and_deleted_customer_do_not_receive_reset_code(): void
    {
        $this->post('/portal/forgot-password', ['phone' => '09120000000'])
            ->assertRedirect('/portal/forgot-password/verify');
        $this->assertNull($this->sender->code);

        $inactive = $this->customerWithPhone('09121111111', false);
        $this->post('/portal/forgot-password', ['phone' => '09121111111']);
        $this->assertNull($this->sender->code);

        $deleted = $this->customerWithPhone('09122222222');
        $deleted->delete();
        $this->post('/portal/forgot-password', ['phone' => '09122222222']);
        $this->assertNull($this->sender->code);

        $this->assertDatabaseCount('customer_password_reset_codes', 0);
        $this->assertNotNull($inactive);
    }

    public function test_verified_reset_code_allows_setting_new_password(): void
    {
        $customer = $this->customerWithPhone('09121111111');
        $customer->forceFill(['password' => Hash::make('OldSecure123')])->save();

        $this->post('/portal/forgot-password', ['phone' => '09121111111']);
        $code = $this->sender->code;

        $this->post('/portal/forgot-password/verify', ['code' => $code])
            ->assertRedirect('/portal/forgot-password/reset');

        $this->get('/portal/forgot-password/reset')->assertOk();

        $this->post('/portal/forgot-password/reset', [
            'password' => 'NewSecure123',
            'password_confirmation' => 'NewSecure123',
        ])->assertRedirect('/portal/login');

        $customer->refresh();
        $this->assertTrue(Hash::check('NewSecure123', $customer->password));
        $this->assertNotNull($customer->password_changed_at);
        $this->assertDatabaseMissing('customer_password_reset_codes', [
            'customer_id' => $customer->id,
            'consumed_at' => null,
        ]);
    }

    public function test_invalid_expired_or_reused_reset_code_is_rejected(): void
    {
        $this->customerWithPhone('09121111111');
        $this->post('/portal/forgot-password', ['phone' => '09121111111']);
        $validCode = $this->sender->code;

        $this->post('/portal/forgot-password/verify', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        CustomerPasswordResetCode::query()->update(['expires_at' => now()->subSecond()]);
        $this->post('/portal/forgot-password/verify', ['code' => $validCode])
            ->assertSessionHasErrors('code');

        $this->assertGuest('customer');
    }

    public function test_reset_form_requires_recent_verified_reset_session(): void
    {
        $this->get('/portal/forgot-password/reset')
            ->assertRedirect('/portal/forgot-password');

        $customer = $this->customerWithPhone('09121111111');

        $this->withSession([
            'customer_password_reset_phone' => '09121111111',
            'customer_password_reset_customer_id' => $customer->id,
            'customer_password_reset_verified_at' => now()->subMinutes(20)->timestamp,
        ])->get('/portal/forgot-password/reset')
            ->assertRedirect('/portal/forgot-password');
    }

    public function test_authenticated_customer_can_change_existing_password(): void
    {
        $customer = $this->customerWithPhone('09121111111');
        $customer->forceFill(['password' => Hash::make('OldSecure123')])->save();

        $this->actingAs($customer, 'customer')
            ->put('/portal/profile/password', [
                'current_password' => 'OldSecure123',
                'password' => 'NewSecure123',
                'password_confirmation' => 'NewSecure123',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertTrue(Hash::check('NewSecure123', $customer->password));
        $this->assertNotNull($customer->password_changed_at);
    }

    public function test_wrong_current_password_does_not_change_password(): void
    {
        $customer = $this->customerWithPhone('09121111111');
        $customer->forceFill(['password' => Hash::make('OldSecure123')])->save();

        $this->actingAs($customer, 'customer')
            ->from('/portal/profile')
            ->put('/portal/profile/password', [
                'current_password' => 'WrongSecure123',
                'password' => 'NewSecure123',
                'password_confirmation' => 'NewSecure123',
            ])
            ->assertRedirect('/portal/profile')
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('OldSecure123', $customer->fresh()->password));
    }

    public function test_customer_without_password_can_define_one_from_authenticated_portal(): void
    {
        $customer = $this->customerWithPhone('09121111111');

        $this->actingAs($customer, 'customer')
            ->put('/portal/profile/password', [
                'password' => 'FirstSecure123',
                'password_confirmation' => 'FirstSecure123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('FirstSecure123', $customer->fresh()->password));
    }

    public function test_new_password_policy_requires_uppercase_and_digit(): void
    {
        $customer = $this->customerWithPhone('09121111111');

        $this->actingAs($customer, 'customer')
            ->put('/portal/profile/password', [
                'password' => 'lowercaseonly',
                'password_confirmation' => 'lowercaseonly',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($customer, 'customer')
            ->put('/portal/profile/password', [
                'password' => 'NoDigitsHere',
                'password_confirmation' => 'NoDigitsHere',
            ])
            ->assertSessionHasErrors('password');
    }

    private function customerWithPhone(string $phone, bool $active = true): Customer
    {
        $customer = Customer::factory()->create(['is_active' => $active]);
        CustomerPhone::factory()->for($customer)->create([
            'phone' => $phone,
            'is_primary' => true,
        ]);

        return $customer;
    }
}

class CapturingPasswordResetOtpSender implements PasswordResetOtpSender
{
    public ?string $phone = null;

    public ?string $code = null;

    public function send(string $phone, string $code): void
    {
        $this->phone = $phone;
        $this->code = $code;
    }
}
