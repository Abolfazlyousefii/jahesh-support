<?php

namespace App\Services\Legacy;

use App\Enums\LedgerEntryType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class LegacyDataImporter
{
    /** @var array<string,string> */
    private const TASK_STATUS_MAP = [
        'pending' => TaskStatus::Pending->value,
        'under_review' => TaskStatus::Review->value,
        'in_progress' => TaskStatus::InProgress->value,
        'waiting' => TaskStatus::Paused->value,
        'waiting_for_customer' => TaskStatus::Paused->value,
        'pending_manager_approval' => TaskStatus::Review->value,
        'resolved' => TaskStatus::Completed->value,
        'closed' => TaskStatus::Completed->value,
        'done' => TaskStatus::Completed->value,
    ];

    /** @var array<string,string> */
    private const TASK_PRIORITY_MAP = [
        'urgent' => TaskPriority::Urgent->value,
        'high' => TaskPriority::Important->value,
        'medium' => TaskPriority::Normal->value,
        'low' => TaskPriority::Normal->value,
    ];

    public function analyze(string $defaultAdminRole = 'project-manager'): array
    {
        $this->assertTargetReady();
        $legacy = $this->legacyConnection();
        $this->assertLegacyReady($legacy);

        $customers = $legacy->table('customers')->orderBy('id')->get();
        $users = $legacy->table('users')->orderBy('id')->get();
        $tasks = $legacy->table('tasks')->orderBy('id')->get();

        $staff = $users->filter(fn ($user) => $user->role !== 'customer')->values();
        $customerUsers = $users->filter(fn ($user) => $user->role === 'customer')->values();

        $invalidCustomerPhones = [];
        $normalizedCustomerPhones = [];

        foreach ($customers as $customer) {
            $phone = PhoneNormalizer::normalize((string) $customer->phone);

            if (! PhoneNormalizer::isValid($phone)) {
                $invalidCustomerPhones[] = [
                    'legacy_id' => (int) $customer->id,
                    'name' => (string) $customer->name,
                    'phone' => (string) $customer->phone,
                ];

                continue;
            }

            $normalizedCustomerPhones[] = $phone;
        }

        $invalidStaffPhones = [];
        $normalizedStaffPhones = [];

        foreach ($staff as $user) {
            $phone = PhoneNormalizer::normalize((string) $user->phone);

            if (! PhoneNormalizer::isValid($phone)) {
                $invalidStaffPhones[] = [
                    'legacy_id' => (int) $user->id,
                    'name' => (string) $user->name,
                ];

                continue;
            }

            $normalizedStaffPhones[] = $phone;
        }

        $duplicateCustomerPhones = $this->duplicates($normalizedCustomerPhones);
        $duplicateStaffPhones = $this->duplicates($normalizedStaffPhones);

        $staffIds = $staff->pluck('id')->map(fn ($id) => (int) $id)->all();
        $customerIds = $customers->pluck('id')->map(fn ($id) => (int) $id)->all();

        $missingTaskCreators = $tasks
            ->pluck('created_by')
            ->filter(fn ($id) => ! in_array((int) $id, $staffIds, true))
            ->unique()
            ->values()
            ->all();

        $missingTaskAssignees = $tasks
            ->pluck('assigned_to')
            ->filter(fn ($id) => ! in_array((int) $id, $staffIds, true))
            ->unique()
            ->values()
            ->all();

        $missingTaskCustomers = $tasks
            ->pluck('customer_id')
            ->filter()
            ->filter(fn ($id) => ! in_array((int) $id, $customerIds, true))
            ->unique()
            ->values()
            ->all();

        $unknownTaskStatuses = $tasks
            ->pluck('status')
            ->filter(fn ($status) => ! isset(self::TASK_STATUS_MAP[(string) $status]))
            ->unique()
            ->values()
            ->all();

        $unknownTaskPriorities = $tasks
            ->pluck('priority')
            ->filter(fn ($priority) => ! isset(self::TASK_PRIORITY_MAP[(string) $priority]))
            ->unique()
            ->values()
            ->all();

        $existingCustomers = empty($normalizedCustomerPhones)
            ? 0
            : DB::table('customer_phones')->whereIn('phone', array_values(array_unique($normalizedCustomerPhones)))->count();

        $existingStaff = empty($normalizedStaffPhones)
            ? 0
            : DB::table('users')->whereIn('phone', array_values(array_unique($normalizedStaffPhones)))->count();

        $debtCount = $this->legacyTableCount($legacy, 'financial_debts');
        $paymentCount = $this->legacyTableCount($legacy, 'financial_payments');

        $warnings = [];

        foreach ($invalidCustomerPhones as $invalid) {
            $warnings[] = sprintf(
                'شماره مشتری قدیمی #%d (%s) معتبر نیست؛ در Import واقعی مشتری بدون شماره ساخته می‌شود و باید بعداً شماره او اصلاح شود.',
                $invalid['legacy_id'],
                $invalid['name'],
            );
        }

        if ($duplicateCustomerPhones !== []) {
            $warnings[] = 'در دیتابیس قدیمی شماره تکراری بین مشتریان وجود دارد: '.implode(', ', $duplicateCustomerPhones);
        }

        if ($duplicateStaffPhones !== []) {
            $warnings[] = 'در دیتابیس قدیمی شماره تکراری بین اعضای تیم وجود دارد.';
        }

        if ($invalidStaffPhones !== []) {
            $warnings[] = 'حداقل یک عضو تیم قدیمی شماره معتبر ندارد و Import ایمن کاربران ممکن نیست.';
        }

        if ($unknownTaskStatuses !== []) {
            $warnings[] = 'وضعیت ناشناخته تسک وجود دارد: '.implode(', ', $unknownTaskStatuses);
        }

        if ($unknownTaskPriorities !== []) {
            $warnings[] = 'اولویت ناشناخته تسک وجود دارد: '.implode(', ', $unknownTaskPriorities);
        }

        if ($missingTaskCreators !== [] || $missingTaskAssignees !== [] || $missingTaskCustomers !== []) {
            $warnings[] = 'برخی روابط تسک‌ها به User/Customer معتبر اشاره نمی‌کنند.';
        }

        return [
            'source' => (string) config('legacy-import.source', 'jahesh-v1'),
            'default_admin_role' => $defaultAdminRole,
            'customers' => $customers->count(),
            'valid_customer_phones' => count($normalizedCustomerPhones),
            'invalid_customer_phones' => $invalidCustomerPhones,
            'existing_customer_matches' => $existingCustomers,
            'staff_users' => $staff->count(),
            'customer_login_users_skipped' => $customerUsers->count(),
            'existing_staff_matches' => $existingStaff,
            'tasks' => $tasks->count(),
            'soft_deleted_tasks' => $tasks->filter(fn ($task) => $task->deleted_at !== null)->count(),
            'task_statuses' => $tasks->groupBy('status')->map->count()->all(),
            'task_priorities' => $tasks->groupBy('priority')->map->count()->all(),
            'financial_debts' => $debtCount,
            'financial_debt_total' => $this->legacySum($legacy, 'financial_debts', 'final_amount'),
            'financial_payments' => $paymentCount,
            'financial_payment_total' => $this->legacyValidPaymentSum($legacy),
            'legacy_tickets' => $this->legacyTableCount($legacy, 'tickets'),
            'projects_deferred' => $this->legacyTableCount($legacy, 'projects'),
            'leads_deferred' => $this->legacyTableCount($legacy, 'leads'),
            'schedules_deferred' => $this->legacyTableCount($legacy, 'schedules'),
            'missing_task_creators' => $missingTaskCreators,
            'missing_task_assignees' => $missingTaskAssignees,
            'missing_task_customers' => $missingTaskCustomers,
            'unknown_task_statuses' => $unknownTaskStatuses,
            'unknown_task_priorities' => $unknownTaskPriorities,
            'duplicate_customer_phones' => $duplicateCustomerPhones,
            'duplicate_staff_phones' => $duplicateStaffPhones,
            'invalid_staff_phones' => $invalidStaffPhones,
            'warnings' => $warnings,
        ];
    }

    public function import(string $defaultAdminRole = 'project-manager', bool $strict = false): array
    {
        $analysis = $this->analyze($defaultAdminRole);
        $this->assertImportable($analysis, $strict);

        if (! DB::table('roles')->where('name', $defaultAdminRole)->where('guard_name', 'web')->exists()) {
            throw new RuntimeException("نقش {$defaultAdminRole} در سیستم جدید وجود ندارد. ابتدا Seeder نقش‌ها و دسترسی‌ها را اجرا کنید.");
        }

        $legacy = $this->legacyConnection();

        return DB::transaction(function () use ($legacy, $defaultAdminRole): array {
            $report = [
                'customers_created' => 0,
                'customers_matched' => 0,
                'customers_mapped' => 0,
                'customer_phones_created' => 0,
                'customers_without_phone' => 0,
                'staff_created' => 0,
                'staff_matched' => 0,
                'staff_mapped' => 0,
                'customer_users_represented_by_customer' => 0,
                'tasks_created' => 0,
                'tasks_already_mapped' => 0,
                'ledger_debits_created' => 0,
                'ledger_credits_created' => 0,
                'finance_already_mapped' => 0,
            ];

            $this->importCustomers($legacy, $report);
            $this->importStaff($legacy, $defaultAdminRole, $report);
            $this->mapLegacyCustomerUsers($legacy, $report);
            $this->importTasks($legacy, $report);
            $this->importFinance($legacy, $report);

            return $report;
        });
    }

    private function importCustomers(Connection $legacy, array &$report): void
    {
        $customers = $legacy->table('customers')->orderBy('id')->get();

        foreach ($customers as $legacyCustomer) {
            $legacyId = (int) $legacyCustomer->id;

            if ($this->mappedId('customer', $legacyId, 'customers') !== null) {
                $report['customers_mapped']++;
                continue;
            }

            $phone = PhoneNormalizer::normalize((string) $legacyCustomer->phone);
            $phoneIsValid = PhoneNormalizer::isValid($phone);
            $currentId = null;

            if ($phoneIsValid) {
                $existingPhone = DB::table('customer_phones')->where('phone', $phone)->first();

                if ($existingPhone) {
                    $currentId = (int) $existingPhone->customer_id;
                    $report['customers_matched']++;
                }
            }

            if ($currentId === null) {
                $currentId = (int) DB::table('customers')->insertGetId([
                    'name' => (string) $legacyCustomer->name,
                    'company_name' => $legacyCustomer->company_name,
                    'city' => null,
                    'address' => $legacyCustomer->address,
                    'notes' => null,
                    'is_active' => $legacyCustomer->status === 'active',
                    'created_at' => $legacyCustomer->created_at ?? now(),
                    'updated_at' => $legacyCustomer->updated_at ?? $legacyCustomer->created_at ?? now(),
                    'deleted_at' => null,
                ]);

                $report['customers_created']++;

                if ($phoneIsValid) {
                    DB::table('customer_phones')->insert([
                        'customer_id' => $currentId,
                        'phone' => $phone,
                        'is_primary' => true,
                        'created_at' => $legacyCustomer->created_at ?? now(),
                        'updated_at' => $legacyCustomer->updated_at ?? $legacyCustomer->created_at ?? now(),
                    ]);

                    $report['customer_phones_created']++;
                } else {
                    $report['customers_without_phone']++;
                }
            }

            $this->recordMap('customer', $legacyId, $currentId, [
                'legacy_phone' => (string) $legacyCustomer->phone,
                'phone_imported' => $phoneIsValid,
            ]);
        }
    }

    private function importStaff(Connection $legacy, string $defaultAdminRole, array &$report): void
    {
        $staff = $legacy->table('users')
            ->where('role', '!=', 'customer')
            ->orderBy('id')
            ->get();

        foreach ($staff as $legacyUser) {
            $legacyId = (int) $legacyUser->id;

            if ($this->mappedId('staff_user', $legacyId, 'users') !== null) {
                $report['staff_mapped']++;
                continue;
            }

            $phone = PhoneNormalizer::normalize((string) $legacyUser->phone);

            if (! PhoneNormalizer::isValid($phone)) {
                throw new RuntimeException("شماره عضو تیم قدیمی #{$legacyId} معتبر نیست.");
            }

            $existing = DB::table('users')->where('phone', $phone)->first();
            $created = false;

            if ($existing) {
                $currentId = (int) $existing->id;
                $report['staff_matched']++;
            } else {
                $currentId = (int) DB::table('users')->insertGetId([
                    'name' => (string) $legacyUser->name,
                    'phone' => $phone,
                    'password' => (string) $legacyUser->password,
                    'is_active' => true,
                    'last_login_at' => null,
                    'remember_token' => null,
                    'created_at' => $legacyUser->created_at ?? now(),
                    'updated_at' => $legacyUser->updated_at ?? $legacyUser->created_at ?? now(),
                    'deleted_at' => null,
                ]);

                $created = true;
                $report['staff_created']++;
            }

            $user = User::withTrashed()->findOrFail($currentId);

            if ($created || $user->roles()->count() === 0) {
                $user->assignRole($this->roleForLegacyStaff((string) $legacyUser->role, $defaultAdminRole));
            }

            $this->recordMap('staff_user', $legacyId, $currentId, [
                'legacy_role' => (string) $legacyUser->role,
                'matched_existing' => ! $created,
            ]);
        }
    }

    private function mapLegacyCustomerUsers(Connection $legacy, array &$report): void
    {
        $customerUsers = $legacy->table('users')
            ->where('role', 'customer')
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->get();

        foreach ($customerUsers as $legacyUser) {
            $legacyUserId = (int) $legacyUser->id;

            if ($this->mappedId('customer_user', $legacyUserId, 'customers') !== null) {
                continue;
            }

            $customerId = $this->mappedId('customer', (int) $legacyUser->customer_id, 'customers');

            if ($customerId === null) {
                throw new RuntimeException("Customer مربوط به کاربر مشتری قدیمی #{$legacyUserId} پیدا نشد.");
            }

            $this->recordMap('customer_user', $legacyUserId, $customerId, [
                'represented_as' => 'customer',
            ]);

            $report['customer_users_represented_by_customer']++;
        }
    }

    private function importTasks(Connection $legacy, array &$report): void
    {
        $tasks = $legacy->table('tasks')->orderBy('id')->get();

        foreach ($tasks as $legacyTask) {
            $legacyId = (int) $legacyTask->id;

            if ($this->mappedId('task', $legacyId, 'tasks') !== null) {
                $report['tasks_already_mapped']++;
                continue;
            }

            $creatorId = $this->mappedId('staff_user', (int) $legacyTask->created_by, 'users');
            $assigneeId = $this->mappedId('staff_user', (int) $legacyTask->assigned_to, 'users');

            if ($creatorId === null || $assigneeId === null) {
                throw new RuntimeException("User رابطه تسک قدیمی #{$legacyId} پیدا نشد.");
            }

            $customerId = null;

            if ($legacyTask->customer_id !== null) {
                $customerId = $this->mappedId('customer', (int) $legacyTask->customer_id, 'customers');

                if ($customerId === null) {
                    throw new RuntimeException("Customer رابطه تسک قدیمی #{$legacyId} پیدا نشد.");
                }
            }

            $status = self::TASK_STATUS_MAP[(string) $legacyTask->status] ?? null;
            $priority = self::TASK_PRIORITY_MAP[(string) $legacyTask->priority] ?? null;

            if ($status === null || $priority === null) {
                throw new RuntimeException("Status/Priority تسک قدیمی #{$legacyId} قابل تبدیل نیست.");
            }

            $completedAt = null;

            if ($status === TaskStatus::Completed->value) {
                $completedAt = $legacyTask->completed_at
                    ?? $legacyTask->resolved_at
                    ?? $legacyTask->closed_at
                    ?? $legacyTask->updated_at
                    ?? now();
            }

            $currentId = (int) DB::table('tasks')->insertGetId([
                'title' => (string) $legacyTask->title,
                'description' => $legacyTask->description,
                'customer_id' => $customerId,
                'source_ticket_id' => null,
                'assignee_id' => $assigneeId,
                'created_by' => $creatorId,
                'priority' => $priority,
                'status' => $status,
                'start_date' => $this->dateOnly($legacyTask->start_date),
                'due_date' => $this->dateOnly($legacyTask->deadline),
                'completed_at' => $completedAt,
                'created_at' => $legacyTask->created_at ?? now(),
                'updated_at' => $legacyTask->updated_at ?? $legacyTask->created_at ?? now(),
                'deleted_at' => $legacyTask->deleted_at,
            ]);

            $this->recordMap('task', $legacyId, $currentId, [
                'legacy_project_id' => $legacyTask->project_id,
                'legacy_ticket_id' => $legacyTask->ticket_id,
                'legacy_status' => (string) $legacyTask->status,
                'legacy_priority' => (string) $legacyTask->priority,
                'legacy_deadline_at' => $legacyTask->deadline,
            ]);

            $report['tasks_created']++;
        }
    }

    private function importFinance(Connection $legacy, array &$report): void
    {
        if (Schema::connection('legacy_import')->hasTable('financial_debts')) {
            $debts = $legacy->table('financial_debts')->orderBy('id')->get();

            foreach ($debts as $debt) {
                $legacyId = (int) $debt->id;

                if ($this->mappedId('financial_debt', $legacyId, 'customer_ledger_entries') !== null) {
                    $report['finance_already_mapped']++;
                    continue;
                }

                $customerId = $this->mappedId('customer', (int) $debt->customer_id, 'customers');

                if ($customerId === null) {
                    throw new RuntimeException("Customer سند بدهی قدیمی #{$legacyId} پیدا نشد.");
                }

                $createdBy = $debt->created_by
                    ? $this->mappedId('staff_user', (int) $debt->created_by, 'users')
                    : null;

                $voidedAt = null;
                $voidReason = null;

                if ($debt->deleted_at !== null || in_array((string) $debt->status, ['draft', 'cancelled'], true)) {
                    $voidedAt = $debt->cancelled_at ?? $debt->deleted_at ?? $debt->updated_at ?? now();
                    $voidReason = $debt->cancel_reason ?: 'سند قدیمی غیرقطعی یا لغوشده';
                }

                $currentId = (int) DB::table('customer_ledger_entries')->insertGetId([
                    'customer_id' => $customerId,
                    'type' => LedgerEntryType::Debit->value,
                    'amount' => $this->moneyToInteger($debt->final_amount),
                    'description' => $this->financeDescription($debt->title, $debt->description),
                    'reference' => $this->reference($debt->tracking_code, 'LEGACY-DEBT-'.$legacyId),
                    'entry_date' => $debt->registered_at,
                    'source' => 'legacy_financial_debt',
                    'payment_receipt_id' => null,
                    'created_by' => $createdBy,
                    'voided_at' => $voidedAt,
                    'voided_by' => $debt->cancelled_by
                        ? $this->mappedId('staff_user', (int) $debt->cancelled_by, 'users')
                        : null,
                    'void_reason' => $voidReason,
                    'created_at' => $debt->created_at ?? now(),
                    'updated_at' => $debt->updated_at ?? $debt->created_at ?? now(),
                ]);

                $this->recordMap('financial_debt', $legacyId, $currentId, [
                    'legacy_project_id' => $debt->project_id,
                    'legacy_customer_expense_id' => $debt->customer_expense_id,
                    'legacy_status' => (string) $debt->status,
                ]);

                $report['ledger_debits_created']++;
            }
        }

        if (Schema::connection('legacy_import')->hasTable('financial_payments')) {
            $debtsById = $legacy->table('financial_debts')->get()->keyBy('id');
            $payments = $legacy->table('financial_payments')->orderBy('id')->get();

            foreach ($payments as $payment) {
                $legacyId = (int) $payment->id;

                if ($this->mappedId('financial_payment', $legacyId, 'customer_ledger_entries') !== null) {
                    $report['finance_already_mapped']++;
                    continue;
                }

                $debt = $debtsById->get($payment->financial_debt_id);

                if (! $debt) {
                    throw new RuntimeException("Debt مربوط به پرداخت قدیمی #{$legacyId} پیدا نشد.");
                }

                $customerId = $this->mappedId('customer', (int) $debt->customer_id, 'customers');

                if ($customerId === null) {
                    throw new RuntimeException("Customer پرداخت قدیمی #{$legacyId} پیدا نشد.");
                }

                $createdBy = $payment->created_by
                    ? $this->mappedId('staff_user', (int) $payment->created_by, 'users')
                    : null;

                $voidedAt = null;
                $voidReason = null;

                if (! (bool) $payment->is_valid || $payment->deleted_at !== null) {
                    $voidedAt = $payment->deleted_at ?? $payment->updated_at ?? now();
                    $voidReason = 'پرداخت قدیمی نامعتبر یا حذف‌شده';
                }

                $currentId = (int) DB::table('customer_ledger_entries')->insertGetId([
                    'customer_id' => $customerId,
                    'type' => LedgerEntryType::Credit->value,
                    'amount' => $this->moneyToInteger($payment->amount),
                    'description' => $this->financeDescription(
                        'پرداخت ثبت‌شده در سیستم قبلی',
                        $payment->description,
                    ),
                    'reference' => $this->reference($payment->tracking_code, 'LEGACY-PAYMENT-'.$legacyId),
                    'entry_date' => $payment->paid_at,
                    'source' => 'legacy_financial_payment',
                    'payment_receipt_id' => null,
                    'created_by' => $createdBy,
                    'voided_at' => $voidedAt,
                    'voided_by' => null,
                    'void_reason' => $voidReason,
                    'created_at' => $payment->created_at ?? now(),
                    'updated_at' => $payment->updated_at ?? $payment->created_at ?? now(),
                ]);

                $this->recordMap('financial_payment', $legacyId, $currentId, [
                    'legacy_financial_debt_id' => $payment->financial_debt_id,
                    'legacy_is_valid' => (bool) $payment->is_valid,
                ]);

                $report['ledger_credits_created']++;
            }
        }
    }

    private function assertTargetReady(): void
    {
        foreach ([
            'users',
            'roles',
            'model_has_roles',
            'customers',
            'customer_phones',
            'tasks',
            'customer_ledger_entries',
            'legacy_import_maps',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("جدول {$table} در دیتابیس جدید وجود ندارد. ابتدا php artisan migrate را اجرا کنید.");
            }
        }
    }

    private function assertLegacyReady(Connection $legacy): void
    {
        try {
            $legacy->getPdo();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'اتصال به دیتابیس قدیمی برقرار نشد. تنظیمات LEGACY_DB_* را بررسی کنید.',
                previous: $exception,
            );
        }

        foreach (['customers', 'users', 'tasks'] as $table) {
            if (! Schema::connection('legacy_import')->hasTable($table)) {
                throw new RuntimeException("جدول {$table} در دیتابیس قدیمی پیدا نشد.");
            }
        }
    }

    private function assertImportable(array $analysis, bool $strict): void
    {
        $hardErrors = [
            $analysis['duplicate_customer_phones'] !== [],
            $analysis['duplicate_staff_phones'] !== [],
            $analysis['invalid_staff_phones'] !== [],
            $analysis['missing_task_creators'] !== [],
            $analysis['missing_task_assignees'] !== [],
            $analysis['missing_task_customers'] !== [],
            $analysis['unknown_task_statuses'] !== [],
            $analysis['unknown_task_priorities'] !== [],
        ];

        if (in_array(true, $hardErrors, true)) {
            throw new RuntimeException('Dry Run دارای خطای ساختاری است؛ قبل از Import واقعی موارد گزارش‌شده را اصلاح کنید.');
        }

        if ($strict && $analysis['invalid_customer_phones'] !== []) {
            throw new RuntimeException('در حالت strict شماره نامعتبر مشتری مجاز نیست.');
        }
    }

    private function legacyConnection(): Connection
    {
        $connection = config('legacy-import.connection');

        if (! is_array($connection)) {
            throw new RuntimeException('تنظیمات config/legacy-import.php معتبر نیست.');
        }

        config(['database.connections.legacy_import' => $connection]);
        DB::purge('legacy_import');

        return DB::connection('legacy_import');
    }

    private function mappedId(string $entityType, int $legacyId, string $targetTable): ?int
    {
        $map = DB::table('legacy_import_maps')
            ->where('source', $this->source())
            ->where('entity_type', $entityType)
            ->where('legacy_id', $legacyId)
            ->first();

        if (! $map) {
            return null;
        }

        if (DB::table($targetTable)->where('id', $map->current_id)->exists()) {
            return (int) $map->current_id;
        }

        DB::table('legacy_import_maps')->where('id', $map->id)->delete();

        return null;
    }

    private function recordMap(string $entityType, int $legacyId, int $currentId, array $meta = []): void
    {
        $key = [
            'source' => $this->source(),
            'entity_type' => $entityType,
            'legacy_id' => $legacyId,
        ];

        $existing = DB::table('legacy_import_maps')->where($key)->first();

        $data = [
            'current_id' => $currentId,
            'meta' => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('legacy_import_maps')->where('id', $existing->id)->update($data);

            return;
        }

        DB::table('legacy_import_maps')->insert($key + $data + ['created_at' => now()]);
    }

    private function source(): string
    {
        return (string) config('legacy-import.source', 'jahesh-v1');
    }

    private function roleForLegacyStaff(string $legacyRole, string $defaultAdminRole): string
    {
        return match ($legacyRole) {
            'admin' => $defaultAdminRole,
            'website_manager' => 'project-manager',
            default => 'team-member',
        };
    }

    private function dateOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 10);
    }

    private function moneyToInteger(mixed $value): int
    {
        return max(0, (int) round((float) $value));
    }

    private function financeDescription(mixed $title, mixed $description): string
    {
        $parts = array_values(array_filter([
            trim((string) $title),
            trim((string) $description),
        ], fn ($value) => $value !== ''));

        $text = implode(' — ', $parts);

        return mb_substr($text !== '' ? $text : 'سند مالی منتقل‌شده از سیستم قبلی', 0, 500);
    }

    private function reference(mixed $reference, string $fallback): string
    {
        $value = trim((string) $reference);

        return mb_substr($value !== '' ? $value : $fallback, 0, 150);
    }

    /** @param array<int,string> $values */
    private function duplicates(array $values): array
    {
        $counts = array_count_values($values);

        return array_values(array_keys(array_filter($counts, fn ($count) => $count > 1)));
    }

    private function legacyTableCount(Connection $legacy, string $table): int
    {
        return Schema::connection('legacy_import')->hasTable($table)
            ? $legacy->table($table)->count()
            : 0;
    }

    private function legacySum(Connection $legacy, string $table, string $column): int
    {
        if (! Schema::connection('legacy_import')->hasTable($table)) {
            return 0;
        }

        return $this->moneyToInteger($legacy->table($table)->sum($column));
    }

    private function legacyValidPaymentSum(Connection $legacy): int
    {
        if (! Schema::connection('legacy_import')->hasTable('financial_payments')) {
            return 0;
        }

        return $this->moneyToInteger(
            $legacy->table('financial_payments')
                ->where('is_valid', true)
                ->whereNull('deleted_at')
                ->sum('amount'),
        );
    }
}
