<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'پنل مشتری' }} | جهش</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($portalCustomer = auth('customer')->user())
<body class="portal-body min-h-screen">
    <div class="portal-shell">
        <aside class="portal-sidebar" aria-label="منوی پنل مشتری">
            <a href="{{ route('portal.dashboard') }}" class="portal-brand">
                <span class="portal-brand-mark">ج</span>
                <span>
                    <strong>پشتیبانی جهش</strong>
                    <small>پنل مشتریان</small>
                </span>
            </a>

            <nav class="portal-nav">
                <a href="{{ route('portal.dashboard') }}" class="portal-nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}" @if(request()->routeIs('portal.dashboard')) aria-current="page" @endif>
                    <x-icon name="dashboard" />
                    <span>خانه</span>
                </a>
                <a href="{{ route('portal.tickets.index') }}" class="portal-nav-link {{ request()->routeIs('portal.tickets.*') ? 'active' : '' }}" @if(request()->routeIs('portal.tickets.*')) aria-current="page" @endif>
                    <x-icon name="tickets" />
                    <span>تیکت‌ها</span>
                </a>
                <a href="{{ route('portal.finance.index') }}" class="portal-nav-link {{ request()->routeIs('portal.finance.*') ? 'active' : '' }}" @if(request()->routeIs('portal.finance.*')) aria-current="page" @endif>
                    <x-icon name="finance" />
                    <span>مالی و حساب</span>
                </a>
                <a href="{{ route('portal.profile') }}" class="portal-nav-link {{ request()->routeIs('portal.profile') ? 'active' : '' }}" @if(request()->routeIs('portal.profile')) aria-current="page" @endif>
                    <x-icon name="customers" />
                    <span>حساب من</span>
                </a>
            </nav>

            <div class="portal-sidebar-spacer"></div>

            <div class="portal-help-card">
                <span>پشتیبانی</span>
                <strong>سؤالی دارید یا مشکلی پیش آمده؟</strong>
                <a href="{{ route('portal.tickets.create') }}">ثبت درخواست جدید</a>
            </div>
        </aside>

        <div class="portal-main">
            <header class="portal-topbar">
                <div class="portal-topbar-title">
                    <strong>{{ $title ?? 'پنل مشتری' }}</strong>
                    <span>مدیریت درخواست‌ها و خدمات شما</span>
                </div>

                <div class="portal-user">
                    <div class="portal-user-avatar">{{ mb_substr($portalCustomer?->name ?? 'ج', 0, 1) }}</div>
                    <div class="portal-user-copy">
                        <strong>{{ $portalCustomer?->name }}</strong>
                        <span>{{ $portalCustomer?->company_name ?: 'مشتری جهش' }}</span>
                    </div>
                    <form method="POST" action="{{ route('portal.logout') }}">
                        @csrf
                        <button class="portal-logout" type="submit" aria-label="خروج از حساب">
                            <x-icon name="logout" />
                            <span>خروج</span>
                        </button>
                    </form>
                </div>
            </header>

            <header class="portal-mobile-header">
                <a href="{{ route('portal.dashboard') }}" class="portal-mobile-brand">
                    <span class="portal-brand-mark">ج</span>
                    <strong>پشتیبانی جهش</strong>
                </a>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button class="portal-mobile-logout" type="submit">خروج</button>
                </form>
            </header>

            <main class="portal-content">
                <x-alert />
                {{ $slot }}
            </main>
        </div>
    </div>

    <nav class="portal-mobile-nav" aria-label="منوی موبایل پنل مشتری">
        <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
            <x-icon name="dashboard" />
            <span>خانه</span>
        </a>
        <a href="{{ route('portal.tickets.index') }}" class="{{ request()->routeIs('portal.tickets.*') ? 'active' : '' }}">
            <x-icon name="tickets" />
            <span>تیکت‌ها</span>
        </a>
        <a href="{{ route('portal.finance.index') }}" class="{{ request()->routeIs('portal.finance.*') ? 'active' : '' }}">
            <x-icon name="finance" />
            <span>مالی</span>
        </a>
        <a href="{{ route('portal.profile') }}" class="{{ request()->routeIs('portal.profile') ? 'active' : '' }}">
            <x-icon name="customers" />
            <span>حساب من</span>
        </a>
    </nav>
</body>
</html>
