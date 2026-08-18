@php
    $defaultPhones = $customer
        ? $customer->phones->map(fn ($phone) => ['phone' => $phone->phone])->values()->all()
        : [['phone' => '']];
    $defaultPrimary = $customer
        ? max(0, $customer->phones->search(fn ($phone) => $phone->is_primary))
        : 0;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-input label="نام مشتری" name="name" :value="$customer?->name" required />
    <x-input label="نام شرکت / فروشگاه / مجموعه" name="company_name" :value="$customer?->company_name" />
    <x-input label="شهر" name="city" :value="$customer?->city" />
    <label class="flex min-h-11 items-center gap-2 self-end rounded-lg border border-gray-200 px-3">
        <input type="checkbox" name="is_active" value="1" class="accent-emerald-500" @checked(old('is_active', $customer?->is_active ?? true))>
        <span>مشتری فعال باشد</span>
    </label>
</div>

<div class="mt-5 grid gap-4 sm:grid-cols-2">
    <div>
        <label for="address" class="form-label">آدرس</label>
        <textarea id="address" name="address" rows="4" class="form-control">{{ old('address', $customer?->address) }}</textarea>
        @error('address')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="notes" class="form-label">یادداشت داخلی</label>
        <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes', $customer?->notes) }}</textarea>
        @error('notes')<p class="form-error">{{ $message }}</p>@enderror
    </div>
</div>

<section
    class="mt-6 rounded-xl border border-gray-200 p-4 sm:p-5"
    x-data="{
        password: '',
        confirmation: '',
        showPassword: false,
        showConfirmation: false,
        submitted: false,
        get hasPassword() { return this.password.length > 0 },
        get hasLength() { return this.password.length >= 8 },
        get hasUppercase() { return /[A-Z]/.test(this.password) },
        get hasNumber() { return /[0-9]/.test(this.password) },
        get passwordValid() { return !this.hasPassword || (this.hasLength && this.hasUppercase && this.hasNumber) },
        get confirmationValid() { return !this.hasPassword || (this.confirmation.length > 0 && this.confirmation === this.password) },
        ruleClass(valid) {
            if (!this.hasPassword) return 'text-gray-500';
            return valid ? 'text-emerald-700' : 'text-red-600';
        },
        submitForm(event) {
            if (!event.target.contains(this.$refs.password)) return;
            this.submitted = true;

            if (this.hasPassword && (!this.passwordValid || !this.confirmationValid)) {
                event.preventDefault();

                this.$nextTick(() => {
                    if (!this.passwordValid) {
                        this.$refs.password.focus();
                    } else {
                        this.$refs.confirmation.focus();
                    }
                });
            }
        }
    }"
    @submit.window="submitForm($event)"
>
    <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-sm font-bold text-gray-900">ورود به پنل مشتری</h2>
            <p class="mt-1 text-xs leading-6 text-gray-500">
                {{ $customer ? 'برای تغییر رمز، رمز جدید را وارد کنید. خالی گذاشتن این بخش رمز فعلی را حفظ می‌کند.' : 'اختیاری است؛ مشتری همچنان می‌تواند با کد یکبار مصرف وارد شود.' }}
            </p>
        </div>
        @if($customer)
            <span class="mt-2 inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-bold {{ filled($customer->password) ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }} sm:mt-0">
                {{ filled($customer->password) ? 'رمز تعریف شده' : 'بدون رمز' }}
            </span>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="form-label">{{ $customer ? 'رمز عبور جدید' : 'رمز عبور' }}</label>

            <div class="relative">
                <input
                    x-ref="password"
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    x-model="password"
                    class="form-control pl-11"
                    autocomplete="new-password"
                    dir="ltr"
                    placeholder="رمز عبور را وارد کنید"
                    aria-describedby="customer-password-rules"
                >

                <button
                    type="button"
                    class="absolute left-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور'"
                    :title="showPassword ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور'"
                >
                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                        <circle cx="12" cy="12" r="2.75"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.6 5.4A10.5 10.5 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a17.7 17.7 0 0 1-2.42 3.18M6.18 6.18C3.72 8.06 2.25 12 2.25 12S6 18.75 12 18.75c1.54 0 2.94-.44 4.17-1.08M9.88 9.88a3 3 0 0 0 4.24 4.24"/>
                    </svg>
                </button>
            </div>

            <div id="customer-password-rules" class="mt-3 space-y-1.5 text-xs leading-5">
                <div class="flex items-center gap-2" :class="ruleClass(hasLength)">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border text-[10px] font-bold" x-text="hasPassword && hasLength ? '✓' : '•'"></span>
                    <span>حداقل ۸ کاراکتر</span>
                </div>
                <div class="flex items-center gap-2" :class="ruleClass(hasUppercase)">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border text-[10px] font-bold" x-text="hasPassword && hasUppercase ? '✓' : '•'"></span>
                    <span>حداقل یک حرف بزرگ انگلیسی (A-Z)</span>
                </div>
                <div class="flex items-center gap-2" :class="ruleClass(hasNumber)">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border text-[10px] font-bold" x-text="hasPassword && hasNumber ? '✓' : '•'"></span>
                    <span>حداقل یک عدد (0-9)</span>
                </div>
            </div>

            @error('password')
                <p class="form-error mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="form-label">تکرار رمز عبور</label>

            <div class="relative">
                <input
                    x-ref="confirmation"
                    id="password_confirmation"
                    name="password_confirmation"
                    :type="showConfirmation ? 'text' : 'password'"
                    x-model="confirmation"
                    class="form-control pl-11"
                    autocomplete="new-password"
                    dir="ltr"
                    placeholder="رمز عبور را دوباره وارد کنید"
                >

                <button
                    type="button"
                    class="absolute left-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    @click="showConfirmation = !showConfirmation"
                    :aria-label="showConfirmation ? 'مخفی کردن تکرار رمز عبور' : 'نمایش تکرار رمز عبور'"
                    :title="showConfirmation ? 'مخفی کردن تکرار رمز عبور' : 'نمایش تکرار رمز عبور'"
                >
                    <svg x-show="!showConfirmation" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                        <circle cx="12" cy="12" r="2.75"/>
                    </svg>
                    <svg x-show="showConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.6 5.4A10.5 10.5 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a17.7 17.7 0 0 1-2.42 3.18M6.18 6.18C3.72 8.06 2.25 12 2.25 12S6 18.75 12 18.75c1.54 0 2.94-.44 4.17-1.08M9.88 9.88a3 3 0 0 0 4.24 4.24"/>
                    </svg>
                </button>
            </div>

            <div class="mt-3 min-h-5 text-xs leading-5">
                <p x-show="hasPassword && confirmation.length === 0" class="text-gray-500">
                    تکرار رمز عبور را وارد کنید.
                </p>
                <p x-show="hasPassword && confirmation.length > 0 && confirmation !== password" class="flex items-center gap-2 text-red-600">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border text-[10px] font-bold">×</span>
                    <span>تکرار رمز عبور با رمز اصلی یکسان نیست.</span>
                </p>
                <p x-show="hasPassword && confirmation.length > 0 && confirmation === password" class="flex items-center gap-2 text-emerald-700">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border text-[10px] font-bold">✓</span>
                    <span>رمزها با یکدیگر مطابقت دارند.</span>
                </p>
            </div>
        </div>
    </div>

    <p x-show="submitted && hasPassword && (!passwordValid || !confirmationValid)" class="mt-3 text-xs font-medium text-red-600">
        قبل از ذخیره، تمام شرایط رمز عبور را کامل کنید.
    </p>
</section>

<fieldset
    class="mt-6 rounded-xl border border-gray-200 p-4"
    x-data="{
        phones: @js(old('phones', $defaultPhones)),
        primary: String(@js(old('primary_phone', $defaultPrimary))),
        add() { this.phones.push({ phone: '' }) },
        remove(index) { this.phones.splice(index, 1); this.primary = '0' }
    }"
>
    <legend class="px-2 text-sm font-bold">شماره‌های موبایل <span class="text-red-600">*</span></legend>
    <div class="space-y-3">
        <template x-for="(item, index) in phones" :key="index">
            <div class="flex flex-col gap-2 rounded-lg bg-gray-50 p-3 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <label class="form-label" :for="'phone-' + index" x-text="index === 0 ? 'شماره موبایل' : 'شماره دیگر'"></label>
                    <input class="form-control" :id="'phone-' + index" :name="'phones[' + index + '][phone]'" x-model="item.phone" inputmode="numeric" dir="ltr" required>
                </div>
                <label class="flex min-h-11 shrink-0 items-center gap-2 px-2">
                    <input type="radio" name="primary_phone" :value="index" x-model="primary" class="accent-emerald-500">
                    <span>شماره اصلی</span>
                </label>
                <button type="button" class="btn btn-danger shrink-0" x-show="phones.length > 1" @click="remove(index)">حذف</button>
            </div>
        </template>
    </div>
    @foreach($errors->get('phones.*.phone') as $messages)
        @foreach($messages as $message)<p class="form-error">{{ $message }}</p>@endforeach
    @endforeach
    @error('phones')<p class="form-error">{{ $message }}</p>@enderror
    @error('primary_phone')<p class="form-error">{{ $message }}</p>@enderror
    <button type="button" class="btn btn-secondary mt-3" @click="add()">+ افزودن شماره دیگر</button>
</fieldset>

<div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row">
    <a href="{{ $customer ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-secondary">انصراف</a>
    <x-button>ذخیره</x-button>
</div>
