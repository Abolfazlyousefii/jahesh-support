<?php

namespace Tests\Feature;

use App\Models\SmsLog;
use App\Models\SmsPattern;
use App\Models\SmsSetting;
use App\Jobs\SendPatternSmsJob;
use App\Contracts\OtpSender;
use App\Models\User;
use App\Services\Sms\SmsPatternCatalog;
use App\Services\Sms\SmsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        SmsPatternCatalog::ensureStored();
    }

    public function test_sms_settings_route_is_protected(): void
    {
        $this->get('/settings/sms')->assertRedirect('/login');

        $member = User::factory()->create();
        $member->assignRole('team-member');

        $this->actingAs($member)->get('/settings/sms')->assertForbidden();
        $this->actingAs($this->admin)->get('/settings/sms')->assertOk();
    }

    public function test_sms_password_is_encrypted_and_blank_update_keeps_it(): void
    {
        $payload = [
            'enabled' => '1',
            'webservice_username' => 'demo-user',
            'webservice_password' => 'SecretPass123',
            'internal_recipient_user_ids' => [],
            'patterns' => [],
        ];

        $this->actingAs($this->admin)->put('/settings/sms', $payload)->assertRedirect();

        $setting = SmsSetting::current()->refresh();
        $this->assertSame('SecretPass123', $setting->webservice_password);
        $this->assertNotSame('SecretPass123', DB::table('sms_settings')->value('webservice_password'));

        $payload['webservice_password'] = '';
        $this->actingAs($this->admin)->put('/settings/sms', $payload)->assertRedirect();

        $this->assertSame('SecretPass123', SmsSetting::current()->refresh()->webservice_password);
    }

    public function test_melipayamak_pattern_send_uses_official_rest_endpoint_and_logs_result(): void
    {
        SmsSetting::current()->update([
            'enabled' => true,
            'webservice_username' => 'user',
            'webservice_password' => 'pass',
        ]);

        SmsPattern::query()->where('key', 'customer_otp')->update([
            'enabled' => true,
            'body_id' => 12345,
        ]);

        Http::fake([
            'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber' => Http::response(['Value' => '987654321'], 200),
        ]);

        $log = app(SmsService::class)->sendNow('customer_otp', '09121111111', ['456789']);

        $this->assertSame(SmsLog::STATUS_SENT, $log->status);
        $this->assertSame('987654321', $log->provider_message_id);

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber'
            && $request['username'] === 'user'
            && $request['password'] === 'pass'
            && $request['to'] === '09121111111'
            && (int) $request['bodyId'] === 12345
            && $request['text'] === '456789'
        );
    }



    public function test_otp_sender_uses_melipayamak_when_sms_is_enabled(): void
    {
        config()->set('jahesh.otp.driver', 'auto');

        SmsSetting::current()->update([
            'enabled' => true,
            'webservice_username' => 'user',
            'webservice_password' => 'pass',
        ]);

        SmsPattern::query()->where('key', 'customer_otp')->update([
            'enabled' => true,
            'body_id' => 12345,
        ]);

        Http::fake([
            'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber' => Http::response(['Value' => '555001'], 200),
        ]);

        app(OtpSender::class)->send('09121111111', '654321');

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '09121111111',
            'pattern_key' => 'customer_otp',
            'status' => SmsLog::STATUS_SENT,
            'provider_message_id' => '555001',
        ]);
    }

    public function test_enabled_notification_is_logged_and_queued(): void
    {
        Queue::fake();

        SmsSetting::current()->update([
            'enabled' => true,
            'webservice_username' => 'user',
            'webservice_password' => 'pass',
        ]);

        SmsPattern::query()->where('key', 'ticket_created_customer')->update([
            'enabled' => true,
            'body_id' => 22222,
        ]);

        $log = app(SmsService::class)->queue(
            'ticket_created_customer',
            '09121111111',
            ['مشتری', 10],
            'ticket',
            10,
        );

        $this->assertNotNull($log);
        $this->assertSame(SmsLog::STATUS_QUEUED, $log->status);
        Queue::assertPushed(SendPatternSmsJob::class, fn ($job) => $job->smsLogId === $log->id);
    }

    public function test_disabled_sms_does_not_queue_notifications(): void
    {
        SmsSetting::current()->update(['enabled' => false]);

        $log = app(SmsService::class)->queue('ticket_created_customer', '09121111111', ['مشتری', 1]);

        $this->assertNull($log);
        $this->assertDatabaseCount('sms_logs', 0);
    }
}
