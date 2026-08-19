<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Finance\BankAccountController;
use App\Http\Controllers\Finance\CustomerFinanceController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\LedgerEntryController;
use App\Http\Controllers\Finance\PaymentReceiptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Portal\CustomerAuthController;
use App\Http\Controllers\Portal\CustomerPasswordResetController;
use App\Http\Controllers\Portal\PortalPasswordController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalProfileController;
use App\Http\Controllers\Portal\PortalFinanceController;
use App\Http\Controllers\Portal\PortalNotificationController;
use App\Http\Controllers\Portal\PortalPaymentReceiptController;
use App\Http\Controllers\Portal\PortalTicketController;
use App\Http\Controllers\Portal\PortalTicketReplyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\SmsSettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketConversionController;
use App\Http\Controllers\TicketMessageController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('customer.guest')->group(function () {
        Route::get('/login', [CustomerAuthController::class, 'create'])->name('login');
        Route::post('/login/password', [CustomerAuthController::class, 'passwordLogin'])->middleware('throttle:6,1')->name('login.password');
        Route::post('/login', [CustomerAuthController::class, 'requestCode'])->middleware('throttle:customer-otp-request')->name('login.request');
        Route::get('/verify', [CustomerAuthController::class, 'verification'])->name('verify');
        Route::post('/verify', [CustomerAuthController::class, 'verify'])->middleware('throttle:customer-otp-verify')->name('verify.store');
        Route::post('/resend', [CustomerAuthController::class, 'resend'])->middleware('throttle:customer-otp-request')->name('resend');
        Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])->name('password.forgot');
        Route::post('/forgot-password', [CustomerPasswordResetController::class, 'requestCode'])->middleware('throttle:customer-password-reset-request')->name('password.request');
        Route::get('/forgot-password/verify', [CustomerPasswordResetController::class, 'verification'])->name('password.verify');
        Route::post('/forgot-password/verify', [CustomerPasswordResetController::class, 'verify'])->middleware('throttle:customer-password-reset-verify')->name('password.verify.store');
        Route::post('/forgot-password/resend', [CustomerPasswordResetController::class, 'resend'])->middleware('throttle:customer-password-reset-request')->name('password.resend');
        Route::get('/forgot-password/reset', [CustomerPasswordResetController::class, 'resetForm'])->name('password.reset');
        Route::post('/forgot-password/reset', [CustomerPasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::middleware(['customer.auth', 'customer.active'])->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');
        Route::get('/dashboard/active-tickets', [PortalDashboardController::class, 'activeTickets'])->name('dashboard.active-tickets');
        Route::get('/profile', PortalProfileController::class)->name('profile');
        Route::get('/notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/summary', [PortalNotificationController::class, 'summary'])->name('notifications.summary');
        Route::post('/notifications/{notification}/open', [PortalNotificationController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [PortalNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::put('/profile/password', [PortalPasswordController::class, 'update'])->middleware('throttle:6,1')->name('profile.password.update');
        Route::get('/finance', PortalFinanceController::class)->name('finance.index');
        Route::post('/finance/receipts', [PortalPaymentReceiptController::class, 'store'])->name('finance.receipts.store');
        Route::get('/finance/receipts/{receipt}/file', [PortalPaymentReceiptController::class, 'file'])->name('finance.receipts.file');
        Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
        Route::get('/tickets', [PortalTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [PortalTicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [PortalTicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [PortalTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/replies', [PortalTicketReplyController::class, 'store'])->name('tickets.replies.store');
    });
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/summary', [NotificationController::class, 'summary'])->name('notifications.summary');
    Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::prefix('activity')->name('activity.')->middleware('can:activity.view')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activity}', [ActivityLogController::class, 'show'])->name('show');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->middleware('can:finance.view')->name('index');
        Route::get('/customers/{customer}', [CustomerFinanceController::class, 'show'])->middleware('can:finance.view')->name('customers.show');
        Route::post('/customers/{customer}/entries', [CustomerFinanceController::class, 'storeEntry'])->middleware('can:finance.create_entry')->name('customers.entries.store');
        Route::patch('/entries/{entry}/void', [LedgerEntryController::class, 'void'])->middleware('can:finance.void_entry')->name('entries.void');

        Route::get('/receipts', [PaymentReceiptController::class, 'index'])->middleware('can:finance.view')->name('receipts.index');
        Route::get('/receipts/{receipt}', [PaymentReceiptController::class, 'show'])->middleware('can:finance.view')->name('receipts.show');
        Route::get('/receipts/{receipt}/file', [PaymentReceiptController::class, 'file'])->middleware('can:finance.view')->name('receipts.file');
        Route::patch('/receipts/{receipt}/approve', [PaymentReceiptController::class, 'approve'])->middleware('can:finance.review_payments')->name('receipts.approve');
        Route::patch('/receipts/{receipt}/reject', [PaymentReceiptController::class, 'reject'])->middleware('can:finance.review_payments')->name('receipts.reject');

        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->middleware('can:finance.manage_bank_accounts')->name('bank-accounts.index');
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->middleware('can:finance.manage_bank_accounts')->name('bank-accounts.store');
        Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->middleware('can:finance.manage_bank_accounts')->name('bank-accounts.update');
        Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->middleware('can:finance.manage_bank_accounts')->name('bank-accounts.destroy');
    });

    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->middleware('can:tickets.view')->name('index');
        Route::get('/{ticket}', [TicketController::class, 'show'])->middleware('can:tickets.view')->name('show');
        Route::post('/{ticket}/reply', [TicketMessageController::class, 'publicReply'])->middleware('can:tickets.reply')->name('reply');
        Route::post('/{ticket}/internal-note', [TicketMessageController::class, 'internalNote'])->middleware('can:tickets.internal_notes')->name('internal-note');
        Route::patch('/{ticket}/assignment', [TicketAssignmentController::class, 'update'])->middleware('can:tickets.assign')->name('assignment.update');
        Route::patch('/{ticket}/status', [TicketStatusController::class, 'update'])->middleware('can:tickets.update_status')->name('status.update');
        Route::get('/{ticket}/convert', [TicketConversionController::class, 'create'])->middleware('can:tickets.convert_to_task')->name('convert');
        Route::post('/{ticket}/convert', [TicketConversionController::class, 'store'])->middleware('can:tickets.convert_to_task')->name('convert.store');
        Route::delete('/{ticket}', [TicketController::class, 'destroy'])->middleware('can:tickets.delete')->name('destroy');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->middleware('can:tasks.view')->name('index');
        Route::get('/create', [TaskController::class, 'create'])->middleware('can:tasks.create')->name('create');
        Route::post('/', [TaskController::class, 'store'])->middleware('can:tasks.create')->name('store');
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->middleware('can:tasks.update_status')->name('status.update');
        Route::get('/{task}', [TaskController::class, 'show'])->middleware('can:tasks.view')->name('show');
        Route::get('/{task}/edit', [TaskController::class, 'edit'])->middleware('can:tasks.update')->name('edit');
        Route::put('/{task}', [TaskController::class, 'update'])->middleware('can:tasks.update')->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->middleware('can:tasks.delete')->name('destroy');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->middleware('can:customers.view')->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->middleware('can:customers.create')->name('create');
        Route::post('/', [CustomerController::class, 'store'])->middleware('can:customers.create')->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->middleware('can:customers.view')->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->middleware('can:customers.update')->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->middleware('can:customers.update')->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->middleware('can:customers.delete')->name('destroy');
    });

    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->middleware('can:team.view')->name('index');
        Route::get('/create', [TeamController::class, 'create'])->middleware('can:team.create')->name('create');
        Route::post('/', [TeamController::class, 'store'])->middleware('can:team.create')->name('store');
        Route::get('/{user}/edit', [TeamController::class, 'edit'])->middleware('can:team.update')->name('edit');
        Route::put('/{user}', [TeamController::class, 'update'])->middleware('can:team.update')->name('update');
        Route::delete('/{user}', [TeamController::class, 'destroy'])->middleware('can:team.delete')->name('destroy');
    });


    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general', [GeneralSettingsController::class, 'index'])
            ->middleware('can:settings.general.manage')
            ->name('general.index');
        Route::put('/general', [GeneralSettingsController::class, 'update'])
            ->middleware('can:settings.general.manage')
            ->name('general.update');

        Route::get('/sms', [SmsSettingsController::class, 'index'])
            ->middleware('can:settings.sms.manage')
            ->name('sms.index');
        Route::put('/sms', [SmsSettingsController::class, 'update'])
            ->middleware('can:settings.sms.manage')
            ->name('sms.update');
        Route::post('/sms/test-connection', [SmsSettingsController::class, 'testConnection'])
            ->middleware('can:settings.sms.manage')
            ->name('sms.test-connection');
        Route::post('/sms/test-pattern', [SmsSettingsController::class, 'testPattern'])
            ->middleware('can:settings.sms.manage')
            ->name('sms.test-pattern');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('can:roles.view')->name('index');
        Route::get('/create', [RoleController::class, 'create'])->middleware('can:roles.create')->name('create');
        Route::post('/', [RoleController::class, 'store'])->middleware('can:roles.create')->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.update')->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:roles.update')->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('destroy');
    });
});
