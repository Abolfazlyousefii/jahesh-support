<?php

namespace App\Services\Activity;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Models\FinancialBankAccount;
use App\Models\GeneralSetting;
use App\Models\Role;
use App\Models\SmsSetting;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;
use UnitEnum;

class ActivityLogger
{
    private const SENSITIVE_FRAGMENTS = [
        'password',
        'secret',
        'token',
        'otp',
        'code_hash',
        'remember_token',
        'api_key',
        'webservice_password',
    ];

    /**
     * ثبت رویداد به‌صورت best-effort؛ خرابی گزارش فعالیت نباید عملیات اصلی کاربر را Rollback کند.
     *
     * @param array<string,mixed> $old
     * @param array<string,mixed> $new
     * @param array<string,mixed> $metadata
     */
    public function record(
        string $event,
        ?Model $subject = null,
        ?Model $actor = null,
        string $description = '',
        array $old = [],
        array $new = [],
        array $metadata = [],
    ): ?ActivityLog {
        try {
            $actor ??= $this->currentActor();

            return ActivityLog::query()->create([
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
                'actor_name' => $this->actorName($actor),
                'event' => $event,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'subject_label' => $this->subjectLabel($subject),
                'description' => $description,
                'old_values' => $this->sanitize($old),
                'new_values' => $this->sanitize($new),
                'metadata' => $this->sanitize($metadata),
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole()
                    ? null
                    : mb_substr((string) request()->userAgent(), 0, 500),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * فقط فیلدهایی را برمی‌گرداند که واقعاً تغییر کرده‌اند.
     *
     * @param array<string,mixed> $before
     * @param array<string,mixed> $after
     * @return array{old:array<string,mixed>,new:array<string,mixed>}
     */
    public function changed(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $beforeValue = $this->normalize($before[$key] ?? null);
            $afterValue = $this->normalize($after[$key] ?? null);

            if ($beforeValue === $afterValue) {
                continue;
            }

            $old[$key] = $beforeValue;
            $new[$key] = $afterValue;
        }

        return ['old' => $old, 'new' => $new];
    }

    /** @return array<string,mixed> */
    public function snapshot(Model $model, array $fields): array
    {
        return collect($fields)
            ->mapWithKeys(fn (string $field) => [$field => $this->normalize($model->getAttribute($field))])
            ->all();
    }

    private function currentActor(): ?Model
    {
        $web = Auth::guard('web')->user();
        if ($web instanceof Model) {
            return $web;
        }

        $customer = Auth::guard('customer')->user();

        return $customer instanceof Model ? $customer : null;
    }

    private function actorName(?Model $actor): ?string
    {
        return match (true) {
            $actor instanceof User => $actor->name,
            $actor instanceof Customer => $actor->name,
            default => $actor !== null ? class_basename($actor).' #'.$actor->getKey() : null,
        };
    }

    private function subjectLabel(?Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Customer => $subject->company_name
                ? "{$subject->name} ({$subject->company_name})"
                : $subject->name,
            $subject instanceof Task => "#{$subject->id} {$subject->title}",
            $subject instanceof Ticket => "#{$subject->id} {$subject->subject}",
            $subject instanceof CustomerLedgerEntry => "سند #{$subject->id}",
            $subject instanceof CustomerPaymentReceipt => "فیش #{$subject->id}",
            $subject instanceof FinancialBankAccount => $subject->bank_name.' - '.$subject->account_holder,
            $subject instanceof User => $subject->name,
            $subject instanceof Role => $subject->title,
            $subject instanceof SmsSetting => 'تنظیمات پیامک',
            $subject instanceof GeneralSetting => 'تنظیمات عمومی',
            default => $subject !== null ? class_basename($subject).' #'.$subject->getKey() : null,
        };
    }

    private function sanitize(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitiveKey($key)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $itemKey => $itemValue) {
                $result[$itemKey] = $this->sanitize($itemValue, is_string($itemKey) ? $itemKey : null);
            }

            return $result;
        }

        return $this->normalize($value);
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Model) {
            return $value->getKey();
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = mb_strtolower($key);

        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
