<x-layouts.guest title="ورود">
    <section class="w-full max-w-sm">
        <div class="mb-7 text-center"><span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-lg bg-brand text-lg font-black text-emerald-950">ج</span><strong class="text-lg">جهش</strong><p class="mt-1 text-sm text-gray-500">پنل مدیریت و پشتیبانی تیم جهش</p></div>
        <div class="panel p-5 sm:p-7"><h1 class="mb-5 text-xl font-bold">ورود به پنل جهش</h1><x-alert />
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">@csrf
                <x-input label="شماره موبایل" name="phone" inputmode="numeric" autocomplete="username" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required />
                <x-input label="رمز عبور" name="password" type="password" autocomplete="current-password" required />
                <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-emerald-500" @checked(old('remember'))> مرا به خاطر بسپار</label>
                <x-button class="w-full">ورود</x-button>
            </form>
        </div>
    </section>
</x-layouts.guest>
