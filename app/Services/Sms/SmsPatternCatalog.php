<?php

namespace App\Services\Sms;

use App\Models\SmsPattern;
use Illuminate\Support\Collection;

final class SmsPatternCatalog
{
    /** @return array<string,array{title:string,parameters:array<int,string>,sample:array<int,string>,template:string}> */
    public static function definitions(): array
    {
        return [
            'customer_otp' => [
                'title' => 'کد ورود مشتری',
                'parameters' => ['کد ورود'],
                'sample' => ['123456'],
                'template' => 'کد ورود شما به پنل جهش: {0}',
            ],
            'customer_password_reset_otp' => [
                'title' => 'کد بازیابی رمز عبور مشتری',
                'parameters' => ['کد بازیابی'],
                'sample' => ['123456'],
                'template' => 'کد بازیابی رمز عبور پنل جهش: {0}',
            ],
            'ticket_created_customer' => [
                'title' => 'تأیید ثبت تیکت برای مشتری',
                'parameters' => ['نام مشتری', 'شماره تیکت'],
                'sample' => ['مشتری جهش', '128'],
                'template' => 'سلام {0}، تیکت #{1} شما در پنل جهش ثبت شد.',
            ],
            'ticket_created_staff' => [
                'title' => 'تیکت جدید برای تیم',
                'parameters' => ['نام همکار', 'شماره تیکت', 'نام مشتری'],
                'sample' => ['علی', '128', 'مشتری جهش'],
                'template' => '{0} عزیز، تیکت جدید #{1} از مشتری {2} ثبت شد.',
            ],
            'ticket_assigned' => [
                'title' => 'ارجاع تیکت به مسئول',
                'parameters' => ['نام همکار', 'شماره تیکت', 'نام مشتری'],
                'sample' => ['علی', '128', 'مشتری جهش'],
                'template' => '{0} عزیز، تیکت #{1} مشتری {2} به شما ارجاع شد.',
            ],
            'ticket_staff_reply' => [
                'title' => 'پاسخ جدید پشتیبانی برای مشتری',
                'parameters' => ['نام مشتری', 'شماره تیکت'],
                'sample' => ['مشتری جهش', '128'],
                'template' => 'سلام {0}، پاسخ جدیدی برای تیکت #{1} شما ثبت شد.',
            ],
            'ticket_customer_reply' => [
                'title' => 'پاسخ جدید مشتری برای مسئول',
                'parameters' => ['نام همکار', 'شماره تیکت', 'نام مشتری'],
                'sample' => ['علی', '128', 'مشتری جهش'],
                'template' => '{0} عزیز، مشتری {2} در تیکت #{1} پاسخ جدید ثبت کرد.',
            ],
            'ticket_resolved' => [
                'title' => 'حل شدن تیکت برای مشتری',
                'parameters' => ['نام مشتری', 'شماره تیکت'],
                'sample' => ['مشتری جهش', '128'],
                'template' => 'سلام {0}، تیکت #{1} شما حل شده است. جزئیات در پنل جهش.',
            ],
            'task_assigned' => [
                'title' => 'ارجاع تسک به عضو تیم',
                'parameters' => ['نام همکار', 'شماره تسک'],
                'sample' => ['علی', '342'],
                'template' => '{0} عزیز، تسک جدید #{1} در پنل جهش به شما ارجاع شد.',
            ],
            'receipt_submitted' => [
                'title' => 'ثبت فیش جدید برای تیم',
                'parameters' => ['نام همکار', 'نام مشتری', 'مبلغ'],
                'sample' => ['علی', 'مشتری جهش', '5,000,000'],
                'template' => '{0} عزیز، فیش جدید مشتری {1} به مبلغ {2} تومان ثبت شد.',
            ],
            'receipt_approved' => [
                'title' => 'تأیید فیش برای مشتری',
                'parameters' => ['نام مشتری', 'مبلغ'],
                'sample' => ['مشتری جهش', '5,000,000'],
                'template' => 'سلام {0}، پرداخت شما به مبلغ {1} تومان تأیید شد.',
            ],
            'receipt_rejected' => [
                'title' => 'رد فیش برای مشتری',
                'parameters' => ['نام مشتری', 'مبلغ'],
                'sample' => ['مشتری جهش', '5,000,000'],
                'template' => 'سلام {0}، فیش پرداخت {1} تومان شما رد شد. لطفاً پنل جهش را بررسی کنید.',
            ],
        ];
    }

    public static function ensureStored(?string $onlyKey = null): void
    {
        foreach (self::definitions() as $key => $definition) {
            if ($onlyKey !== null && $key !== $onlyKey) {
                continue;
            }

            SmsPattern::query()->firstOrCreate(
                ['key' => $key],
                ['title' => $definition['title'], 'enabled' => false],
            );
        }
    }

    /** @return Collection<int,SmsPattern> */
    public static function stored(): Collection
    {
        self::ensureStored();

        $order = array_flip(array_keys(self::definitions()));

        return SmsPattern::query()->whereIn('key', array_keys(self::definitions()))->get()->sortBy(
            fn (SmsPattern $pattern): int => $order[$pattern->key] ?? 999,
        )->values();
    }

    /** @return array<int,string> */
    public static function sampleValues(string $key): array
    {
        return self::definitions()[$key]['sample'] ?? [];
    }
}
