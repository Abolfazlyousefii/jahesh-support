<x-layouts.guest title="بازیابی رمز عبور">
    <section class="w-full max-w-sm">
        <div class="mb-7 text-center">
            <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-lg bg-brand text-lg font-black text-emerald-950">ج</span>
            <strong class="text-lg">{{ $generalSettings['portal.title'] ?? 'پشتیبانی جهش' }}</strong>
            <p class="mt-1 text-sm text-gray-500">بازیابی حساب مشتری</p>
        </div>

        <div class="panel p-5 sm:p-7">
            <h1 class="mb-2 text-xl font-bold">فراموشی رمز عبور</h1>
            <p class="mb-5 text-sm leading-6 text-gray-500">
                شماره موبایلی که برای حساب شما ثبت شده را وارد کنید تا کد بازیابی ارسال شود.
            </p>

            <x-alert />

            <form method="POST" action="{{ route('portal.password.request') }}" class="space-y-4">
                @csrf
                <x-input label="شماره موبایل" name="phone" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required />
                <x-button class="w-full">ارسال کد بازیابی</x-button>
            </form>

            <div class="mt-5 border-t border-gray-100 pt-4 text-center">
                <a href="{{ route('portal.login') }}" class="text-sm font-bold text-gray-600 hover:text-gray-900">بازگشت به صفحه ورود</a>
            </div>
        </div>
    </section>
</x-layouts.guest>
