<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#18212F">
    <title>{{ $title ?? 'پنل مدیریت' }} | {{ $generalSettings['general.app_name'] ?? 'سامانه پشتیبانی جهش' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $staffUser = auth()->user();
    $navigationMetrics = app(\App\Support\NavigationMetrics::class)->for($staffUser);
    $pageDescription = match (true) {
        request()->routeIs('dashboard') => 'مرور وضعیت جاری و موارد نیازمند اقدام',
        request()->routeIs('tasks.*') => 'برنامه‌ریزی و پیگیری کارهای تیم',
        request()->routeIs('tickets.*') => 'رسیدگی به درخواست‌های پشتیبانی مشتریان',
        request()->routeIs('finance.*') => 'مدیریت گردش حساب و پرداخت‌های مشتریان',
        request()->routeIs('customers.*') => 'اطلاعات و ارتباطات مشتریان',
        request()->routeIs('team.*', 'roles.*') => 'مدیریت اعضا، نقش‌ها و دسترسی‌ها',
        request()->routeIs('activity.*') => 'ردیابی رویدادها و تغییرات سامانه',
        request()->routeIs('settings.*') => 'پیکربندی سامانه پشتیبانی',
        request()->routeIs('notifications.*') => 'رویدادهای مرتبط با حساب شما',
        default => 'سامانه مدیریت عملیات تیم جهش',
    };
@endphp
<body x-data="adminShell" @submit.capture="confirmSubmit($event)" class="admin-layout min-h-screen">
    <aside class="admin-sidebar" aria-label="منوی اصلی">
        @include('layouts.sidebar')
    </aside>

    <div x-cloak x-show="drawer" class="fixed inset-0 z-50 lg:hidden" @keydown.escape.window="drawer = false">
        <button type="button" class="absolute inset-0 bg-slate-950/45" @click="drawer = false" aria-label="بستن منو"></button>
        <aside class="mobile-drawer-panel" x-transition:enter.duration.150ms x-transition:leave.duration.150ms @click="if ($event.target.closest('a')) drawer = false">
            <button type="button" class="absolute left-3 top-3 grid h-9 w-9 place-items-center rounded-lg text-slate-400 hover:bg-white/5 hover:text-white" @click="drawer = false" aria-label="بستن منو">
                <x-icon name="close" class="h-5 w-5" />
            </button>
            @include('layouts.sidebar')
        </aside>
    </div>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-heading">
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-gray-600 hover:bg-gray-100 lg:hidden" @click="drawer = true" aria-label="باز کردن منو">
                    <x-icon name="menu" />
                </button>
                <div class="admin-topbar-title">
                    <strong>{{ $title ?? 'پنل مدیریت' }}</strong>
                    <span class="hidden sm:block">{{ $pageDescription }}</span>
                </div>
            </div>

            <div class="admin-topbar-actions">
                <div class="admin-search" x-data="{ open: false, query: '' }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <label for="admin-quick-search" class="sr-only">جستجو در بخش‌های سیستم</label>
                    <input id="admin-quick-search" type="search" x-model="query" @focus="open = true" @input="open = true" placeholder="جستجو در سیستم..." autocomplete="off">
                    <x-icon name="search" />
                    <div x-cloak x-show="open" class="admin-search-results" aria-label="دسترسی سریع">
                        @can('dashboard.view')<a x-show="query === '' || 'داشبورد'.includes(query)" href="{{ route('dashboard') }}"><x-icon name="dashboard" />داشبورد</a>@endcan
                        @can('tickets.view')<a x-show="query === '' || 'تیکت پشتیبانی'.includes(query)" href="{{ route('tickets.index') }}"><x-icon name="tickets" />تیکت‌ها</a>@endcan
                        @can('tasks.view')<a x-show="query === '' || 'تسک کار وظیفه'.includes(query)" href="{{ route('tasks.index') }}"><x-icon name="tasks" />تسک‌ها</a>@endcan
                        @can('customers.view')<a x-show="query === '' || 'مشتری'.includes(query)" href="{{ route('customers.index') }}"><x-icon name="customers" />مشتریان</a>@endcan
                        @can('finance.view')<a x-show="query === '' || 'مالی حساب فیش'.includes(query)" href="{{ route('finance.index') }}"><x-icon name="finance" />مالی مشتریان</a>@endcan
                    </div>
                </div>

                <x-notification-bell />
                @canany(['tasks.create', 'customers.create', 'finance.create_entry'])
                    <div class="relative" x-data="{ createOpen: false }" @click.outside="createOpen = false" @keydown.escape.window="createOpen = false">
                        <button type="button" class="admin-create-button" @click="createOpen = !createOpen" :aria-expanded="createOpen.toString()" aria-label="ایجاد مورد جدید">
                            <x-icon name="plus" />
                            <span>جدید</span>
                            <x-icon name="chevron" class="h-3.5 w-3.5" />
                        </button>
                        <div x-cloak x-show="createOpen" x-transition.opacity.duration.150ms class="admin-create-menu">
                            @can('tasks.create')<a href="{{ route('tasks.create') }}"><x-icon name="tasks" />تسک جدید</a>@endcan
                            @can('customers.create')<a href="{{ route('customers.create') }}"><x-icon name="customers" />مشتری جدید</a>@endcan
                            @can('finance.create_entry')<a href="{{ route('finance.index') }}"><x-icon name="finance" />ثبت سند مالی</a>@endcan
                        </div>
                    </div>
                @endcanany
            </div>
        </header>

        <main class="admin-content">
            <x-alert />
            {{ $slot }}
        </main>
    </div>

    <nav class="admin-mobile-nav" aria-label="منوی موبایل">
        @can('dashboard.view')<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="dashboard" />خانه</a>@endcan
        @can('tickets.view')<a href="{{ route('tickets.index') }}" class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}"><x-icon name="tickets" />تیکت</a>@endcan
        @can('tasks.view')<a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}"><x-icon name="tasks" />تسک</a>@endcan
        @can('finance.view')<a href="{{ route('finance.index') }}" class="{{ request()->routeIs('finance.*') ? 'active' : '' }}"><x-icon name="finance" />حساب</a>@endcan
    </nav>

    <div x-cloak x-show="confirmOpen" class="fixed inset-0 z-[80] grid place-items-end bg-slate-950/40 p-0 sm:place-items-center sm:p-4" @keydown.escape.window="cancelConfirmation()" role="presentation">
        <section class="w-full rounded-t-lg bg-white p-5 shadow-sm sm:max-w-md sm:rounded-lg" @click.outside="cancelConfirmation()" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-description">
            <span class="mb-4 grid h-10 w-10 place-items-center rounded-lg bg-red-50 text-red-600"><x-icon name="shield" class="h-5 w-5" /></span>
            <h2 id="confirm-title" class="text-[15px] font-extrabold text-slate-900">تأیید عملیات</h2>
            <p id="confirm-description" class="mt-2 text-[11px] leading-6 text-gray-500" x-text="confirmMessage"></p>
            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-secondary" @click="cancelConfirmation()">انصراف</button>
                <button x-ref="confirmAccept" type="button" class="btn btn-danger" @click="proceedConfirmation()">بله، ادامه بده</button>
            </div>
        </section>
    </div>
</body>
</html>
