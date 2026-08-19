<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class MelipayamakGateway implements SmsGateway
{
    private const BASE_URL = 'https://rest.payamak-panel.com/api/SendSMS';

    public function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {}

    public function sendPattern(string $to, int $bodyId, array $parameters): SmsSendResult
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(12)
                ->post(self::BASE_URL.'/BaseServiceNumber', [
                    'username' => $this->username,
                    'password' => $this->password,
                    'text' => $this->serializeParameters($parameters),
                    'to' => $to,
                    'bodyId' => $bodyId,
                ]);

            if (! $response->successful()) {
                return new SmsSendResult(false, error: 'HTTP '.$response->status());
            }

            $value = $this->responseValue($response->json(), $response->body());

            if ($value !== null && is_numeric($value) && (float) $value > 0) {
                return new SmsSendResult(true, (string) $value);
            }

            return new SmsSendResult(false, error: $this->providerError($value, $response->body()));
        } catch (ConnectionException $exception) {
            return new SmsSendResult(false, error: 'عدم دسترسی به وب‌سرویس ملی پیامک: '.$exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return new SmsSendResult(false, error: 'خطای غیرمنتظره در ارسال پیامک.');
        }
    }

    public function testConnection(): array
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->retry(1, 250)
                ->post(self::BASE_URL.'/GetCredit', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if (! $response->successful()) {
                return ['ok' => false, 'credit' => null, 'error' => 'HTTP '.$response->status()];
            }

            $value = $this->responseValue($response->json(), $response->body());

            if ($value !== null && is_numeric($value) && (float) $value >= 0) {
                return ['ok' => true, 'credit' => (float) $value, 'error' => null];
            }

            return ['ok' => false, 'credit' => null, 'error' => $this->providerError($value, $response->body())];
        } catch (Throwable $exception) {
            return ['ok' => false, 'credit' => null, 'error' => 'عدم برقراری ارتباط با ملی پیامک.'];
        }
    }

    private function serializeParameters(array $parameters): string
    {
        return collect(array_values($parameters))
            ->map(fn ($value): string => str_replace(';', '،', trim((string) $value)))
            ->implode(';');
    }

    private function responseValue(mixed $json, string $body): string|int|float|null
    {
        if (is_array($json)) {
            return $json['Value'] ?? $json['value'] ?? null;
        }

        $trimmed = trim($body);

        return is_numeric($trimmed) ? $trimmed : null;
    }

    private function providerError(string|int|float|null $value, string $body): string
    {
        if ($value !== null) {
            return 'ملی پیامک پاسخ ناموفق داد (کد '.$value.').';
        }

        return 'پاسخ وب‌سرویس ملی پیامک قابل پردازش نبود.';
    }
}
