<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portal\CustomerAuthController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalProfileController;
use App\Http\Controllers\Portal\PortalTicketController;
use App\Http\Controllers\Portal\PortalTicketReplyController;
use App\Http\Controllers\RoleController;
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
        Route::post('/login', [CustomerAuthController::class, 'requestCode'])->middleware('throttle:customer-otp-request')->name('login.request');
        Route::get('/verify', [CustomerAuthController::class, 'verification'])->name('verify');
        Route::post('/verify', [CustomerAuthController::class, 'verify'])->middleware('throttle:customer-otp-verify')->name('verify.store');
        Route::post('/resend', [CustomerAuthController::class, 'resend'])->middleware('throttle:customer-otp-request')->name('resend');
    });

    Route::middleware(['customer.auth', 'customer.active'])->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');
        Route::get('/profile', PortalProfileController::class)->name('profile');
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

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('can:roles.view')->name('index');
        Route::get('/create', [RoleController::class, 'create'])->middleware('can:roles.create')->name('create');
        Route::post('/', [RoleController::class, 'store'])->middleware('can:roles.create')->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.update')->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:roles.update')->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('destroy');
    });
});
