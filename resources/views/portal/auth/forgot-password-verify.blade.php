<x-layouts.guest title="تأیید کد بازیابی">
    <section class="w-full max-w-sm">
        <div class="panel p-5 sm:p-7">
            <h1 class="mb-2 text-xl font-bold">تأیید کد بازیابی</h1>
            <p class="mb-5 text-sm leading-6 text-gray-500">کد شش‌رقمی ارسال‌شده به شماره ثبت‌شده را وارد کنید.</p>

            <x-alert />

            <form method="POST" action="{{ route('portal.password.verify.store') }}" class="space-y-4">
                @csrf
                <x-input label="کد بازیابی" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" dir="ltr" required autofocus />
                <x-button class="w-full">تأیید کد</x-button>
            </form>

            <div
                class="mt-4 border-t border-gray-100 pt-4"
                x-data="{ remaining: {{ $cooldown }}, timer: null }"
                x-init="timer = setInterval(() => { if (remaining > 0) remaining--; else clearInterval(timer) }, 1000)"
            >
                <form method="POST" action="{{ route('portal.password.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-full" :disabled="remaining > 0">
                        <span x-show="remaining > 0">ارسال مجدد تا <span x-text="remaining"></span> ثانیه</span>
                        <span x-show="remaining === 0">ارسال مجدد کد</span>
                    </button>
                </form>
            </div>

            <div class="mt-3 text-center">
                <a href="{{ route('portal.password.forgot') }}" class="text-xs font-bold text-gray-500 hover:text-gray-800">تغییر شماره موبایل</a>
            </div>
        </div>
    </section>
</x-layouts.guest>
