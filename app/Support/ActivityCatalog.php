<?php

namespace App\Support;

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

final class ActivityCatalog
{
    /** @return array<string,array{label:string,group:string}> */
    public static function events(): array
    {
        return [
            'customer.created' => ['label' => 'ایجاد مشتری', 'group' => 'مشتریان'],
            'customer.updated' => ['label' => 'ویرایش مشتری', 'group' => 'مشتریان'],
            'customer.deleted' => ['label' => 'حذف مشتری', 'group' => 'مشتریان'],
            'customer.password_changed_by_admin' => ['label' => 'تغییر رمز مشتری توسط مدیر', 'group' => 'امنیت'],
            'customer.password_changed' => ['label' => 'تغییر رمز توسط مشتری', 'group' => 'امنیت'],
            'customer.password_reset' => ['label' => 'بازیابی رمز مشتری', 'group' => 'امنیت'],

            'task.created' => ['label' => 'ایجاد تسک', 'group' => 'تسک‌ها'],
            'task.updated' => ['label' => 'ویرایش تسک', 'group' => 'تسک‌ها'],
            'task.assigned' => ['label' => 'ارجاع تسک', 'group' => 'تسک‌ها'],
            'task.status_changed' => ['label' => 'تغییر وضعیت تسک', 'group' => 'تسک‌ها'],
            'task.deleted' => ['label' => 'حذف تسک', 'group' => 'تسک‌ها'],

            'ticket.created' => ['label' => 'ایجاد تیکت', 'group' => 'تیکت‌ها'],
            'ticket.assigned' => ['label' => 'ارجاع تیکت', 'group' => 'تیکت‌ها'],
            'ticket.status_changed' => ['label' => 'تغییر وضعیت تیکت', 'group' => 'تیکت‌ها'],
            'ticket.closed' => ['label' => 'بستن تیکت', 'group' => 'تیکت‌ها'],
            'ticket.converted_to_task' => ['label' => 'تبدیل تیکت به تسک', 'group' => 'تیکت‌ها'],
            'ticket.deleted' => ['label' => 'حذف تیکت', 'group' => 'تیکت‌ها'],

            'finance.ledger_created' => ['label' => 'ثبت سند مالی', 'group' => 'مالی'],
            'finance.ledger_voided' => ['label' => 'ابطال سند مالی', 'group' => 'مالی'],
            'finance.receipt_submitted' => ['label' => 'ثبت فیش پرداخت', 'group' => 'مالی'],
            'finance.receipt_approved' => ['label' => 'تأیید فیش پرداخت', 'group' => 'مالی'],
            'finance.receipt_rejected' => ['label' => 'رد فیش پرداخت', 'group' => 'مالی'],
            'finance.bank_account_created' => ['label' => 'ایجاد حساب بانکی', 'group' => 'مالی'],
            'finance.bank_account_updated' => ['label' => 'ویرایش حساب بانکی', 'group' => 'مالی'],
            'finance.bank_account_deleted' => ['label' => 'حذف حساب بانکی', 'group' => 'مالی'],

            'team.user_created' => ['label' => 'ایجاد عضو تیم', 'group' => 'اعضای تیم'],
            'team.user_updated' => ['label' => 'ویرایش عضو تیم', 'group' => 'اعضای تیم'],
            'team.user_deleted' => ['label' => 'حذف عضو تیم', 'group' => 'اعضای تیم'],
            'team.password_changed' => ['label' => 'تغییر رمز عضو تیم', 'group' => 'امنیت'],
            'team.role_changed' => ['label' => 'تغییر نقش عضو تیم', 'group' => 'دسترسی‌ها'],
            'role.created' => ['label' => 'ایجاد نقش', 'group' => 'دسترسی‌ها'],
            'role.updated' => ['label' => 'ویرایش نقش و دسترسی', 'group' => 'دسترسی‌ها'],
            'role.deleted' => ['label' => 'حذف نقش', 'group' => 'دسترسی‌ها'],

            'settings.sms_updated' => ['label' => 'تغییر تنظیمات پیامک', 'group' => 'تنظیمات'],
            'settings.general_updated' => ['label' => 'تغییر تنظیمات عمومی', 'group' => 'تنظیمات'],
        ];
    }

    public static function eventLabel(string $event): string
    {
        return self::events()[$event]['label'] ?? $event;
    }

    public static function eventGroup(string $event): string
    {
        return self::events()[$event]['group'] ?? 'سیستم';
    }

    /** @return array<string,string> */
    public static function subjectTypes(): array
    {
        return [
            (new Customer)->getMorphClass() => 'مشتری',
            (new Task)->getMorphClass() => 'تسک',
            (new Ticket)->getMorphClass() => 'تیکت',
            (new CustomerLedgerEntry)->getMorphClass() => 'سند مالی',
            (new CustomerPaymentReceipt)->getMorphClass() => 'فیش پرداخت',
            (new FinancialBankAccount)->getMorphClass() => 'حساب بانکی',
            (new User)->getMorphClass() => 'عضو تیم',
            (new Role)->getMorphClass() => 'نقش',
            (new SmsSetting)->getMorphClass() => 'تنظیمات پیامک',
            (new GeneralSetting)->getMorphClass() => 'تنظیمات عمومی',
        ];
    }

    public static function subjectTypeLabel(?string $type): string
    {
        if ($type === null) {
            return 'سیستم';
        }

        return self::subjectTypes()[$type] ?? class_basename($type);
    }

    public static function fieldLabel(string $key): string
    {
        return [
            'name' => 'نام',
            'company_name' => 'نام مجموعه',
            'city' => 'شهر',
            'address' => 'آدرس',
            'notes' => 'یادداشت',
            'is_active' => 'وضعیت فعال',
            'phones' => 'شماره‌های تماس',
            'title' => 'عنوان',
            'description' => 'توضیحات',
            'customer_id' => 'مشتری',
            'assignee_id' => 'مسئول',
            'priority' => 'اولویت',
            'status' => 'وضعیت',
            'start_date' => 'تاریخ شروع',
            'due_date' => 'مهلت انجام',
            'type' => 'نوع',
            'amount' => 'مبلغ',
            'reference' => 'مرجع',
            'entry_date' => 'تاریخ سند',
            'void_reason' => 'دلیل ابطال',
            'bank_account_id' => 'حساب بانکی',
            'bank_name' => 'بانک',
            'account_holder' => 'صاحب حساب',
            'card_number' => 'شماره کارت',
            'iban' => 'شماره شبا',
            'account_number' => 'شماره حساب',
            'sort_order' => 'ترتیب نمایش',
            'paid_at' => 'تاریخ پرداخت',
            'tracking_code' => 'کد پیگیری',
            'rejection_reason' => 'دلیل رد',
            'role_ids' => 'نقش‌ها',
            'permission_ids' => 'دسترسی‌ها',
            'enabled' => 'فعال',
            'provider' => 'ارائه‌دهنده',
            'webservice_username' => 'نام کاربری وب‌سرویس',
            'internal_recipient_user_ids' => 'گیرندگان داخلی',
            'patterns' => 'الگوهای پیامک',
            'general_company_name' => 'نام مجموعه',
            'general_app_name' => 'عنوان نرم‌افزار',
            'general_support_phone' => 'شماره پشتیبانی',
            'general_support_hours' => 'ساعات پاسخگویی',
            'general_support_text' => 'متن کوتاه پشتیبانی',
            'general_pagination_per_page' => 'تعداد آیتم در صفحه',
            'portal_title' => 'عنوان پنل مشتری',
            'portal_welcome_text' => 'متن خوش‌آمدگویی',
            'portal_show_support_phone' => 'نمایش شماره پشتیبانی',
            'portal_show_support_hours' => 'نمایش ساعات پاسخگویی',
            'portal_active_ticket_limit' => 'تعداد تیکت‌های باز داشبورد',
        ][$key] ?? $key;
    }

    public static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'بله' : 'خیر';
        }

        if (is_array($value)) {
            if ($value === []) {
                return '—';
            }

            return collect($value)
                ->map(fn ($item, $key) => is_string($key)
                    ? self::fieldLabel((string) $key).': '.self::formatValue($item)
                    : self::formatValue($item))
                ->implode('، ');
        }

        return (string) $value;
    }
}
