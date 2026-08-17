<x-layouts.guest title="ورود مشتریان">
    <section class="w-full max-w-sm">
        <div class="mb-7 text-center"><span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-lg bg-brand text-lg font-black text-emerald-950">ج</span><strong class="text-lg">پشتیبانی جهش</strong><p class="mt-1 text-sm text-gray-500">ورود مشتریان</p></div>
        <div class="panel p-5 sm:p-7">
            <h1 class="mb-2 text-xl font-bold">ورود به حساب</h1><p class="mb-5 text-sm text-gray-500">شماره موبایل ثبت‌شده خود را وارد کنید.</p><x-alert />
            <form method="POST" action="{{ route('portal.login.request') }}" class="space-y-4">@csrf
                <x-input label="شماره موبایل" name="phone" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required />
                <x-button class="w-full">دریافت کد ورود</x-button>
            </form>
        </div>
    </section>
</x-layouts.guest>
