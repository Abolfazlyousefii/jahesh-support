<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'پنل مدیریت' }} | جهش</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ drawer: false }" class="min-h-screen pb-16 lg:pb-0">
    <aside class="fixed inset-y-0 right-0 z-30 hidden w-64 border-l border-gray-200 bg-white p-4 lg:block">
        <div class="mb-6 flex h-12 items-center gap-3 px-2"><span class="grid h-9 w-9 place-items-center rounded-lg bg-brand font-black text-emerald-950">ج</span><div><strong class="block">تیم جهش</strong><span class="text-xs text-gray-500">پنل مدیریت و پشتیبانی</span></div></div>
        @include('layouts.navigation')
    </aside>
    <div x-cloak x-show="drawer" class="fixed inset-0 z-40 lg:hidden" @keydown.escape.window="drawer=false">
        <button class="absolute inset-0 bg-black/30" @click="drawer=false" aria-label="بستن منو"></button>
        <aside class="relative h-full w-72 bg-white p-4" x-transition><div class="mb-6 flex h-12 items-center justify-between"><strong>تیم جهش</strong><button class="h-11 w-11 text-xl" @click="drawer=false" aria-label="بستن">×</button></div>@include('layouts.navigation')</aside>
    </div>
    <div class="lg:mr-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
            <div class="flex items-center gap-3"><button class="grid h-11 w-11 place-items-center lg:hidden" @click="drawer=true" aria-label="باز کردن منو"><x-icon name="menu" /></button><span class="font-bold">{{ $title ?? 'پنل مدیریت' }}</span></div>
            <div class="flex items-center gap-3"><div class="hidden text-left sm:block"><strong class="block text-sm">{{ auth()->user()->name }}</strong><span class="text-xs text-gray-500">{{ auth()->user()->roles->pluck('title')->join('، ') ?: 'بدون نقش' }}</span></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="grid h-11 w-11 place-items-center rounded-lg border border-gray-200 text-gray-600" title="خروج"><x-icon name="logout" /></button></form></div>
        </header>
        <main class="mx-auto max-w-7xl p-4 sm:p-6"><x-alert />{{ $slot }}</main>
    </div>
    <nav class="fixed inset-x-0 bottom-0 z-30 flex min-h-16 items-center justify-around border-t border-gray-200 bg-white px-2 lg:hidden">
        @can('dashboard.view')<a href="{{ route('dashboard') }}" class="flex min-w-14 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('dashboard') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="dashboard" />داشبورد</a>@endcan
        @can('tasks.view')<a href="{{ route('tasks.index') }}" class="flex min-w-14 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('tasks.*') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="tasks" />تسک‌ها</a>@endcan
        @can('tickets.view')<a href="{{ route('tickets.index') }}" class="flex min-w-14 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('tickets.*') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="tickets" />تیکت‌ها</a>@endcan
        @can('finance.view')<a href="{{ route('finance.index') }}" class="flex min-w-14 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('finance.*') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="finance" />مالی</a>@endcan
        @can('team.view')<a href="{{ route('team.index') }}" class="flex min-w-14 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('team.*') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="users" />اعضا</a>@endcan
    </nav>
</body>
</html>
