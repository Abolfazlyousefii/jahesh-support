<?php

namespace App\Console\Commands;

use App\Services\Legacy\LegacyDataImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import
        {--dry-run : فقط تحلیل و گزارش؛ هیچ دیتایی در دیتابیس جدید نوشته نمی‌شود}
        {--strict : در صورت وجود حتی شماره نامعتبر مشتری، Import واقعی متوقف شود}
        {--staff-role=project-manager : نقش پیش‌فرض کاربران admin سیستم قدیمی}
        {--force : اجرای Import واقعی بدون سوال تأیید}';

    protected $description = 'انتقال امن مشتریان، اعضای تیم، تسک‌ها و اسناد مالی از دیتابیس قدیمی جهش';

    public function handle(LegacyDataImporter $importer): int
    {
        $role = (string) $this->option('staff-role');

        try {
            $analysis = $importer->analyze($role);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('گزارش دیتابیس قدیمی');
        $this->table(
            ['بخش', 'تعداد / مقدار'],
            [
                ['مشتریان', number_format($analysis['customers'])],
                ['شماره معتبر مشتری', number_format($analysis['valid_customer_phones'])],
                ['شماره نامعتبر مشتری', number_format(count($analysis['invalid_customer_phones']))],
                ['مشتری Match شده با دیتای فعلی', number_format($analysis['existing_customer_matches'])],
                ['کاربران داخلی تیم', number_format($analysis['staff_users'])],
                ['کاربران customer قدیمی (به User جدید تبدیل نمی‌شوند)', number_format($analysis['customer_login_users_skipped'])],
                ['عضو تیم Match شده با دیتای فعلی', number_format($analysis['existing_staff_matches'])],
                ['تسک‌ها', number_format($analysis['tasks'])],
                ['تسک Soft Deleted', number_format($analysis['soft_deleted_tasks'])],
                ['اسناد بدهی قدیمی', number_format($analysis['financial_debts'])],
                ['جمع بدهی قدیمی', number_format($analysis['financial_debt_total']).' تومان'],
                ['پرداخت‌های قدیمی', number_format($analysis['financial_payments'])],
                ['جمع پرداخت معتبر قدیمی', number_format($analysis['financial_payment_total']).' تومان'],
                ['تیکت قدیمی', number_format($analysis['legacy_tickets'])],
                ['پروژه‌ها - فعلاً منتقل نمی‌شوند', number_format($analysis['projects_deferred'])],
                ['لیدها - فعلاً منتقل نمی‌شوند', number_format($analysis['leads_deferred'])],
                ['برنامه‌ها - فعلاً منتقل نمی‌شوند', number_format($analysis['schedules_deferred'])],
            ],
        );

        $this->line('Status تسک‌ها: '.json_encode($analysis['task_statuses'], JSON_UNESCAPED_UNICODE));
        $this->line('Priority تسک‌ها: '.json_encode($analysis['task_priorities'], JSON_UNESCAPED_UNICODE));
        $this->line('نقش پیش‌فرض adminهای قدیمی: '.$analysis['default_admin_role']);

        if ($analysis['warnings'] !== []) {
            $this->newLine();
            $this->warn('هشدارها:');

            foreach ($analysis['warnings'] as $warning) {
                $this->warn('• '.$warning);
            }
        }

        if ((bool) $this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry Run تمام شد؛ هیچ داده‌ای در دیتابیس جدید تغییر نکرد.');

            return self::SUCCESS;
        }

        if (! (bool) $this->option('force')) {
            $this->newLine();

            if (! $this->confirm('Import واقعی اجرا شود؟ قبل از ادامه از دیتابیس جدید Backup داشته باشید.', false)) {
                $this->comment('Import لغو شد.');

                return self::SUCCESS;
            }
        }

        try {
            $report = $importer->import($role, (bool) $this->option('strict'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Import با موفقیت و داخل Transaction کامل شد.');
        $this->table(
            ['عملیات', 'تعداد'],
            [
                ['مشتری جدید', $report['customers_created']],
                ['مشتری Match شده', $report['customers_matched']],
                ['مشتری از قبل Map شده', $report['customers_mapped']],
                ['شماره مشتری ایجادشده', $report['customer_phones_created']],
                ['مشتری بدون شماره معتبر', $report['customers_without_phone']],
                ['عضو تیم جدید', $report['staff_created']],
                ['عضو تیم Match شده', $report['staff_matched']],
                ['عضو تیم از قبل Map شده', $report['staff_mapped']],
                ['Customer User قدیمی → Customer جدید', $report['customer_users_represented_by_customer']],
                ['تسک جدید', $report['tasks_created']],
                ['تسک از قبل Map شده', $report['tasks_already_mapped']],
                ['سند بدهکار منتقل‌شده', $report['ledger_debits_created']],
                ['سند بستانکار منتقل‌شده', $report['ledger_credits_created']],
                ['سند مالی از قبل Map شده', $report['finance_already_mapped']],
            ],
        );

        $this->newLine();
        $this->comment('برای اطمینان حالا php artisan test را اجرا کنید.');

        return self::SUCCESS;
    }
}
