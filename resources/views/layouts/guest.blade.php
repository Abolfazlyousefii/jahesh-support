<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#18212F">
    <title>{{ $title ?? 'ورود' }} | {{ $generalSettings['general.app_name'] ?? 'سامانه پشتیبانی جهش' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <main class="guest-shell">
        <aside class="guest-brand-panel">
            <a href="{{ url('/') }}" class="admin-brand border-0 p-0">
                <span class="admin-brand-mark">ج</span>
                <span>
                    <strong>{{ $generalSettings['general.company_name'] ?? 'جهش' }}</strong>
                    <small>{{ $generalSettings['general.app_name'] ?? 'سامانه پشتیبانی' }}</small>
                </span>
            </a>
            <div class="max-w-sm">
                <span class="mb-4 block h-0.5 w-10 bg-emerald-500"></span>
                <h2 class="text-2xl font-extrabold leading-relaxed">پشتیبانی منظم، ارتباط شفاف</h2>
                <p class="mt-3 text-[12px] leading-7 text-slate-400">فضای یکپارچه تیم جهش برای پیگیری درخواست‌ها، وظایف و امور مشتریان.</p>
            </div>
            <p class="text-[10px] text-slate-500">Jahesh Support · سامانه داخلی عملیات</p>
        </aside>
        <section class="guest-form-panel">
            <div class="guest-form-inner">{{ $slot }}</div>
        </section>
    </main>
</body>
</html>
