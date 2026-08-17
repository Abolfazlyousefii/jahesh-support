<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'پشتیبانی' }} | جهش</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen pb-16">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4">
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2"><span class="grid h-9 w-9 place-items-center rounded-lg bg-brand font-black text-emerald-950">ج</span><strong>پشتیبانی جهش</strong></a>
            <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-secondary" type="submit">خروج</button></form>
        </div>
    </header>
    <main class="mx-auto max-w-4xl p-4 sm:p-6"><x-alert />{{ $slot }}</main>
    <nav class="fixed inset-x-0 bottom-0 z-30 flex min-h-16 items-center justify-around border-t border-gray-200 bg-white px-3">
        <a href="{{ route('portal.dashboard') }}" class="flex min-w-20 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('portal.dashboard') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="dashboard" />خانه</a>
        <a href="{{ route('portal.tickets.index') }}" class="flex min-w-20 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('portal.tickets.*') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="tickets" />تیکت‌ها</a>
        <a href="{{ route('portal.profile') }}" class="flex min-w-20 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('portal.profile') ? 'text-emerald-700' : 'text-gray-500' }}"><x-icon name="customers" />حساب من</a>
    </nav>
</body>
</html>
