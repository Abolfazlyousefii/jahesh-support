<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

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
