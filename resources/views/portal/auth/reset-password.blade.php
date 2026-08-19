<x-layouts.guest title="تعیین رمز عبور جدید">
    <section class="w-full max-w-sm" x-data="{ showPassword: false, showConfirmation: false, password: '', confirmation: '' }">
        <div class="panel p-5 sm:p-7">
            <h1 class="mb-2 text-xl font-bold">رمز عبور جدید</h1>
            <p class="mb-5 text-sm leading-6 text-gray-500">برای امنیت حساب، رمز جدید باید حداقل ۸ کاراکتر و شامل حرف بزرگ انگلیسی و عدد باشد.</p>

            <x-alert />

            <form method="POST" action="{{ route('portal.password.update') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="reset-password" class="form-label">رمز عبور جدید</label>
                    <div class="relative">
                        <input id="reset-password" name="password" x-model="password" :type="showPassword ? 'text' : 'password'" class="form-control pl-12" autocomplete="new-password" required>
                        <button type="button" @click="showPassword = !showPassword" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-500" x-text="showPassword ? 'مخفی' : 'نمایش'"></button>
                    </div>
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="reset-password-confirmation" class="form-label">تکرار رمز عبور جدید</label>
                    <div class="relative">
                        <input id="reset-password-confirmation" name="password_confirmation" x-model="confirmation" :type="showConfirmation ? 'text' : 'password'" class="form-control pl-12" autocomplete="new-password" required>
                        <button type="button" @click="showConfirmation = !showConfirmation" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-500" x-text="showConfirmation ? 'مخفی' : 'نمایش'"></button>
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 px-4 py-3 text-xs leading-6">
                    <div class="grid gap-1.5">
                        <span :class="password.length >= 8 ? 'text-emerald-700' : 'text-gray-400'">✓ حداقل ۸ کاراکتر</span>
                        <span :class="/[A-Z]/.test(password) ? 'text-emerald-700' : 'text-gray-400'">✓ حداقل یک حرف بزرگ انگلیسی</span>
                        <span :class="/[0-9]/.test(password) ? 'text-emerald-700' : 'text-gray-400'">✓ حداقل یک عدد</span>
                        <span :class="confirmation.length > 0 && confirmation === password ? 'text-emerald-700' : 'text-gray-400'">✓ تکرار رمز عبور یکسان</span>
                    </div>
                </div>

                <x-button class="w-full">ذخیره رمز جدید</x-button>
            </form>
        </div>
    </section>
</x-layouts.guest>
