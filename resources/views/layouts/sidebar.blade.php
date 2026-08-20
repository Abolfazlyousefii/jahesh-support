@php
    $accountUser = auth()->user();
    $avatarInitials = collect(preg_split('/\s+/u', trim($accountUser->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<a href="{{ route('dashboard') }}" class="admin-brand">
    <span class="admin-brand-mark">ج</span>
    <span class="min-w-0">
        <strong class="truncate">{{ $generalSettings['general.company_name'] ?? 'تیم جهش' }}</strong>
        <small class="truncate">{{ $generalSettings['general.app_name'] ?? 'سامانه پشتیبانی' }}</small>
    </span>
</a>

@include('layouts.navigation')

<footer class="admin-sidebar-footer">
    <div class="admin-system-status">سیستم آنلاین</div>

    <div class="relative" x-data="{ accountOpen: false }" @click.outside="accountOpen = false" @keydown.escape.window="accountOpen = false">
        <button type="button" class="admin-account w-full text-right" @click="accountOpen = !accountOpen" :aria-expanded="accountOpen.toString()">
            <span class="admin-avatar">{{ $avatarInitials ?: 'ج' }}</span>
            <span class="admin-account-copy">
                <strong>{{ $accountUser->name }}</strong>
                <span>{{ $accountUser->roles->pluck('title')->join('، ') ?: 'بدون نقش' }}</span>
            </span>
            <x-icon name="more" class="h-4 w-4 shrink-0 text-slate-500" />
        </button>

        <div x-cloak x-show="accountOpen" x-transition.opacity.duration.150ms class="admin-account-menu">
            <a href="{{ route('notifications.index') }}"><x-icon name="bell" />اعلان‌های من</a>
            @can('settings.general.manage')<a href="{{ route('settings.general.index') }}"><x-icon name="settings" />تنظیمات سامانه</a>@endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><x-icon name="logout" />خروج از حساب</button>
            </form>
        </div>
    </div>
</footer>
